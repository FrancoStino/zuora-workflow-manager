# Sistema di Sincronizzazione Workflow Zuora

## Architettura

### Problema precedente
- I workflow venivano recuperati **via API ogni volta** nella pagina `CustomerWorkflows.php`
- Nessuna relazione Customer-Workflow nel database
- Sync manuale e globale senza separazione per customer

### Soluzione implementata
1. **Relazione di Database**: Customer hasMany Workflow con FK
2. **Service di Sync**: `WorkflowSyncService` gestisce la paginazione Zuora API
3. **Job Asincrono**: `SyncCustomerWorkflows` per background processing
4. **Scheduler**: Sincronizzazione automatica oraria via Laravel Scheduler
5. **Query locale**: La UI legge direttamente dal database (veloce)

## Flusso di Sincronizzazione

```
1. Salvataggio Customer (Filament UI)
   ↓
2. Trigger afterCreate() → SyncCustomerWorkflows::dispatch()
   ↓
3. Job in queue (redis/database)
   ↓
4. WorkflowSyncService::syncCustomerWorkflows()
   ├─ Pagina 1 → HTTP GET /workflows?page=1&page_length=50
   ├─ Pagina 2 → HTTP GET /workflows?page=2&page_length=50
   ├─ ... (finché hasMore=false)
   └─ Salva/Aggiorna/Elimina workflow nel DB
   ↓
5. UI carica workflows dal DB (istantaneo)
```

## Utilizzo

### Comando manuale - sincronizzazione di un customer
```bash
php artisan app:sync-workflows --customer="MyCustomer"
```

### Comando manuale - sincronizzazione di tutti i customer
```bash
php artisan app:sync-workflows --all
```

### Sync automatico (configurato)
- **Ogni ora**: Job asincrono per tutti i customer
- **Ogni giorno alle 02:00**: Comando di sync as backup

## Configurazione della Queue

Per far funzionare i Job asincroni, configurare il file `.env`:

```env
# Usa database queue (no setup aggiuntivo)
QUEUE_CONNECTION=database

# O redis (più veloce)
QUEUE_CONNECTION=redis
```

### Eseguire i Job in coda:
```bash
# Processo worker per elaborare i job
php artisan queue:work

# O tramite Supervisor per produzione (https://laravel.com/docs/queues#supervisor)
```

## Schema Database

```sql
-- Tabella aggiornata: workflows
ALTER TABLE workflows ADD COLUMN customer_id BIGINT UNSIGNED NULLABLE;
ALTER TABLE workflows ADD COLUMN zuora_id VARCHAR(255) UNIQUE;
ALTER TABLE workflows ADD COLUMN last_synced_at TIMESTAMP NULLABLE;
ALTER TABLE workflows ADD FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE;
ALTER TABLE workflows ADD INDEX customer_id;
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

### Verificare i job in coda:
```bash
php artisan queue:failed        # Job falliti
php artisan queue:work --tries=3 --backoff=60  # Retry
```

### Log della sincronizzazione:
```
storage/logs/laravel.log
```

Entries di log:
- `'Workflow sync completed'` - Success con stats
- `'Workflow sync failed'` - Errore con dettagli

## Microarchitettura

### Classe: `WorkflowSyncService`
**Responsabilità**: Orchiestrazione della sincronizzazione
- `syncCustomerWorkflows()`: Entry point, gestisce paginazione Zuora API
- `syncWorkflowRecord()`: Salva/aggiorna singolo workflow
- `deleteStaleWorkflows()`: Cleanup di workflow non più in Zuora

**Design Pattern**: Service Locator (inietta ZuoraService tramite constructor injection)

### Classe: `SyncCustomerWorkflows` (Job)
**Responsabilità**: Esecuzione asincrona
- Implementa `ShouldQueue` per background processing
- Retry automatico (3 volte) con backoff di 60s
- Disaccoppia la UI dal long-running process

### Classe: `Kernel` (Scheduler)
**Responsabilità**: Automazione temporale
- Schedule Job ogni ora per tutti i customer
- Schedule comando di backup ogni notte

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

1. **Paginazione**: Default 50 item/pagina (configurabile in `WorkflowSyncService::DEFAULT_PAGE_SIZE`)
2. **Caching Token**: `ZuoraService` cachizza il token OAuth per 1 ora
3. **Index Database**: Customer_id e zuora_id indicizzati per query veloci
4. **Query Lazy Loading**: `CustomerWorkflows` page usa `select()` per minimizzare dati

## Rollback (se necessario)

```bash
php artisan migrate:rollback
# Tornerà a caricamento via API nella CustomerWorkflows page
```
