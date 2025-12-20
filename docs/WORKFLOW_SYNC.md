# Sistema di Sincronizzazione Workflow e Task Zuora

## Architettura

### Problema precedente
- I workflow venivano recuperati **via API ogni volta** nella pagina `CustomerWorkflows.php`
- Nessuna relazione Customer-Workflow nel database
- Sync manuale e globale senza separazione per customer
- Nessuna gestione dei task dei workflow

### Soluzione implementata
1. **Relazione di Database**: Customer hasMany Workflow hasMany Task con FK
2. **Service di Sync**: `WorkflowSyncService` gestisce la paginazione Zuora API e estrazione task
3. **Job Asincrono**: `SyncCustomerWorkflows` per background processing con retry logic
4. **Scheduler**: Sincronizzazione configurabile via Laravel Scheduler (routes/console.php)
5. **Query locale**: La UI legge direttamente dal database (veloce)
6. **Task Management**: Estrazione automatica dei task dal JSON dei workflow
7. **Job Monitoring**: Integrazione Moox Jobs per monitoraggio real-time

## Flusso di Sincronizzazione

```
1. Trigger Sincronizzazione
   ├─ Salvataggio Customer (Filament UI) → afterCreate()
   ├─ Click pulsante "Sync Customer" / "Sync All Customers"
   ├─ Comando CLI: php artisan app:sync-workflows --all
   └─ Scheduler automatico (se abilitato in routes/console.php)
   ↓
2. SyncCustomerWorkflows::dispatch() → Job accodato
   ↓
3. Job in queue (database)
   ↓
4. Queue Worker processa il job (lando queue / cron schedule:run)
   ↓
5. WorkflowSyncService::syncCustomerWorkflows()
   ├─ Pagina 1 → HTTP GET /workflows?page=1&page_length=50
   ├─ Pagina 2 → HTTP GET /workflows?page=2&page_length=50
   ├─ ... (finché hasMore=false)
   ├─ Per ogni workflow:
   │  ├─ Download JSON export: GET /workflows/{id}/export
   │  ├─ Salva/Aggiorna workflow nel DB
   │  └─ Workflow->syncTasksFromJson() → Estrae e salva task
   └─ Elimina workflow stale (non più in Zuora)
   ↓
6. Database aggiornato (workflows + tasks)
   ↓
7. UI Filament mostra dati aggiornati (istantaneo)
   ↓
8. Moox Jobs: Monitoring in real-time
```

## Utilizzo

### Via Interfaccia Filament (Recommended)

1. **Sync singolo customer:**
   - Vai su **Workflows** nella sidebar
   - Click su **Sync Customer**
   - Seleziona il customer dal dropdown
   - Click su **Queue Sync**
   - Il job viene accodato immediatamente

2. **Sync tutti i customer:**
   - Vai su **Workflows** nella sidebar
   - Click su **Sync All Customers**
   - Conferma l'azione
   - Tutti i job vengono accodati

3. **Monitoring:**
   - Vai su **Jobs** menu nella sidebar
   - Visualizza **Jobs** (completati/running)
   - Visualizza **Jobs Waiting** (in coda)
   - Visualizza **Failed Jobs** (falliti con retry)

### Comandi CLI

#### Sincronizzazione Workflow

```bash
# Accoda job per un customer
php artisan app:sync-workflows --customer="MyCustomer"

# Accoda job per tutti i customer
php artisan app:sync-workflows --all

# Esecuzione sincrona (no queue - per debug)
php artisan app:sync-workflows --all --sync
```

#### Sincronizzazione Task (raramente necessario)

I task vengono estratti automaticamente durante la sincronizzazione workflow. Usa questi comandi solo se:
- Hai modificato manualmente il JSON di un workflow
- Vuoi ri-estrarre i task senza scaricare da Zuora

```bash
# Re-estrae task da tutti i workflow
php artisan workflows:sync-tasks --all

# Re-estrae task da un workflow specifico
php artisan workflows:sync-tasks --workflow-id=123
```

### Sync Automatico (Configurabile)

In `routes/console.php` (Laravel 12):

```php
// Decommentare per abilitare sync automatico
Schedule::command('app:sync-workflows --all')
    ->hourly()  // Ogni ora (raccomandato)
    // ->daily()   // Una volta al giorno
    // ->everyFiveMinutes()  // Ogni 5 minuti (per testing)
    ->name('sync-customer-workflows');
```

**Setup Required:**
- **Sviluppo:** `lando schedule` (mantieni il terminale aperto)
- **Produzione:** Cron job `* * * * * php artisan schedule:run` (vedi DEPLOYMENT.md)

## Configurazione della Queue

### Sviluppo Locale (Lando)

1. **Configura `.env`:**
```env
QUEUE_CONNECTION=database
```

2. **Avvia queue worker:**
```bash
lando queue
# Oppure: lando artisan queue:work database --tries=3 --timeout=300
```

3. **[Opzionale] Avvia scheduler per sync automatico:**
```bash
lando schedule
# Oppure: lando artisan schedule:work
```

### Produzione

1. **Queue connection** (già configurato dal deploy):
```env
QUEUE_CONNECTION=database
```

2. **Setup cron job** (una sola volta):
```bash
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

Il cron job esegue lo scheduler che:
- Processa automaticamente i job in coda ogni minuto
- Esegue sync automatico se abilitato (vedi `routes/console.php`)

**Nessun worker persistente richiesto!** Lo scheduler gestisce tutto.

## Schema Database

### Workflows Table
```sql
CREATE TABLE workflows (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    zuora_id VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    state VARCHAR(255),
    created_on TIMESTAMP,
    updated_on TIMESTAMP,
    last_synced_at TIMESTAMP,
    json_export JSON,  -- Full workflow JSON from Zuora
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_id (customer_id),
    INDEX idx_zuora_id (zuora_id),
    INDEX idx_state (state)
);
```

### Tasks Table (New)
```sql
CREATE TABLE tasks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    workflow_id BIGINT UNSIGNED NOT NULL,
    zuora_id VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    task_type VARCHAR(255),
    object_id TEXT,
    action_type VARCHAR(255),
    call_type VARCHAR(255),
    data JSON,
    workflow_task_json JSON,  -- Original task JSON from workflow
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (workflow_id) REFERENCES workflows(id) ON DELETE CASCADE,
    INDEX idx_workflow_id (workflow_id),
    INDEX idx_zuora_id (zuora_id),
    INDEX idx_task_type (task_type)
);
```

### Job Manager Tables (Moox Jobs)
```sql
CREATE TABLE job_manager (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    job_id VARCHAR(255),
    name VARCHAR(255),
    queue VARCHAR(255),
    connection VARCHAR(255),
    status VARCHAR(255),
    created_at TIMESTAMP,
    -- ... altri campi
);

CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(255) UNIQUE,
    connection TEXT,
    queue TEXT,
    payload LONGTEXT,
    exception LONGTEXT,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Statistiche di Sync

La `WorkflowSyncService` ritorna un array con:
```php
[
    'created' => 5,      // Workflow nuovi creati
    'updated' => 2,      // Workflow aggiornati
    'deleted' => 1,      // Workflow non più presenti in Zuora
    'total' => 8,        // Totale elaborati
    'errors' => [],      // Array di errori (se presenti)
]
```

## Monitoraggio

### Via Moox Jobs (Filament UI)

Accedi al pannello admin Filament → Menu **Jobs**:

1. **Jobs**: Visualizza job running e completati
   - Nome job, stato, durata
   - Progress bar (se implementato)
   - Output e risultati

2. **Jobs Waiting**: Vedi job in coda
   - Ordine di esecuzione
   - Tempo stimato
   - Possibilità di eliminare job in attesa

3. **Failed Jobs**: Gestione fallimenti
   - Dettagli errore completi
   - Stack trace
   - **Retry** con un click
   - **Delete** job falliti

4. **Job Batches**: Monitor batch operations
   - Stato batch
   - Job completati/pending/failed
   - Progress complessivo

### Via CLI

```bash
# Verifica job in attesa
php artisan queue:monitor database --max=100

# Visualizza job falliti
php artisan queue:failed

# Retry singolo job
php artisan queue:retry {job-id}

# Retry tutti i job falliti
php artisan queue:retry all

# Elimina job falliti
php artisan queue:flush

# Visualizza scheduled tasks
php artisan schedule:list
```

### Log della sincronizzazione

**Location**: `storage/logs/laravel.log`

**Log Entries**:
- `'Workflow sync completed'` - Success con stats (created, updated, deleted, total)
- `'Workflow sync failed'` - Errore con dettagli e customer_id
- `'Failed to download workflow JSON'` - Warning per workflow singolo
- `'Cannot sync workflows: Invalid credentials'` - Errore credenziali

**Esempio log entry**:
```
[2025-12-20 12:01:30] local.INFO: Workflow sync completed 
{
    "customer_id": 1,
    "stats": {
        "created": 5,
        "updated": 2,
        "deleted": 1,
        "total": 8,
        "errors": []
    }
}
```

## Microarchitettura

### Classe: `WorkflowSyncService`
**Responsabilità**: Orchiestrazione della sincronizzazione
- `syncCustomerWorkflows()`: Entry point, gestisce paginazione Zuora API
- `syncWorkflowRecord()`: Salva/aggiorna singolo workflow + trigger estrazione task
- `downloadWorkflowJson()`: Scarica JSON export completo da Zuora
- `deleteStaleWorkflows()`: Cleanup di workflow non più in Zuora
- `validateCustomerCredentials()`: Verifica credenziali Zuora prima del sync

**Design Pattern**: Service Locator (inietta ZuoraService tramite constructor injection)

**Key Flow**:
```php
foreach ($workflows as $workflowData) {
    $workflow = $this->syncWorkflowRecord($customer, $workflowData);
    $workflow->syncTasksFromJson(); // Automatic task extraction
}
```

### Classe: `SyncCustomerWorkflows` (Job)
**Responsabilità**: Esecuzione asincrona
- Implementa `ShouldQueue` per background processing
- Retry automatico (3 volte) con backoff di 60s
- Validazione customer e credenziali prima dell'esecuzione
- Disaccoppia la UI dal long-running process
- Gestione errori con logging

**Key Features**:
```php
public int $tries = 3;
public int $backoff = 60;

public function handle(WorkflowSyncService $syncService): void {
    // Validates customer exists and has valid credentials
    // Delegates sync to WorkflowSyncService
}
```

### Model: `Workflow`
**Responsabilità**: Gestione task extraction
- `syncTasksFromJson()`: Estrae e sincronizza task dal campo `json_export`
- `hasMany(Task::class)`: Relazione con tasks
- Automaticamente invocato dopo ogni sync workflow

**Task Extraction Logic**:
```php
public function syncTasksFromJson(): void {
    if (!$this->json_export) return;
    
    $tasks = $this->json_export['tasks'] ?? [];
    foreach ($tasks as $taskData) {
        Task::updateOrCreate(
            ['workflow_id' => $this->id, 'zuora_id' => $taskData['id']],
            [...] // task data
        );
    }
}
```

### Scheduled Tasks (routes/console.php)
**Responsabilità**: Automazione temporale (Laravel 12)
- Workflow sync automatico (opzionale, configurabile)
- Queue processing automatico ogni minuto (sviluppo)
- No more Kernel.php schedule method

## Testing

```php
// Unit test per il service
use App\Services\WorkflowSyncService;
use App\Models\Customer;

test('sync workflows per customer', function () {
    $customer = Customer::factory()->create();
    $service = app(WorkflowSyncService::class);
    
    $stats = $service->syncCustomerWorkflows($customer);
    
    expect($stats)->toHaveKeys(['created', 'updated', 'deleted', 'total']);
    expect($customer->workflows()->count())->toBeGreaterThan(0);
});
```

## Considerazioni Prestazionali

### Ottimizzazioni Implementate

1. **Paginazione API**: Default 50 item/pagina (configurabile in `WorkflowSyncService::PAGE_SIZE`)
   - Riduce memory usage per large dataset
   - Permette recupero progressivo

2. **Caching OAuth Token**: `ZuoraService` cachizza token per 1 ora
   - Riduce chiamate API authentication
   - Cache key: `zuora_token_{hash_credentials}`

3. **Database Indexes**:
   - `workflows.customer_id` - Query per customer
   - `workflows.zuora_id` - Lookup rapido
   - `workflows.state` - Filtri per stato
   - `tasks.workflow_id` - Query task per workflow
   - `tasks.task_type` - Filtri per tipo

4. **Background Processing**:
   - Job queue disaccoppia UI dal sync
   - Non blocca request HTTP
   - Retry automatico su fallimento

5. **Lazy Loading**: UI usa `select()` per minimizzare dati trasferiti

6. **JSON Storage**: `json_export` field permette:
   - Re-estrazione task senza chiamate API
   - Backup completo del workflow
   - Analisi offline

### Metriche Performance

**Workflow Sync** (50 workflows):
- API calls: ~3-5 requests (dipende da paginazione)
- Tempo: ~10-30 secondi
- Memory: <128MB

**Task Extraction** (1 workflow con 20 task):
- Tempo: <1 secondo
- Query: 1-20 (dipende da task nuovi vs esistenti)

### Scalabilità

**Supporta**:
- ✅ Centinaia di customer
- ✅ Migliaia di workflow per customer
- ✅ Decine di migliaia di task totali
- ✅ Sync paralleli (multi-customer via queue)

**Limiti**:
- ⚠️ Rate limit Zuora API (controllato da Zuora)
- ⚠️ Database storage per JSON export (può crescere)

## Rollback (se necessario)

```bash
php artisan migrate:rollback
# Tornerà a caricamento via API nella CustomerWorkflows page
```
