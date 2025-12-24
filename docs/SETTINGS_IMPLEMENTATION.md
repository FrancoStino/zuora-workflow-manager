# Implementazione Spatie Laravel Settings

## Panoramica

Questo documento descrive l'implementazione del sistema di settings usando il plugin Filament Spatie Laravel Settings.

## Pacchetti Utilizzati

- `spatie/laravel-settings` v3.6.0
- `filament/spatie-laravel-settings-plugin` v4.0

## Struttura Implementata

### 1. Classe Settings

**File:** `app/Settings/GeneralSettings.php`

Contiene le seguenti proprietà:

#### Site Information
- `site_name`: Nome del sito (string)
- `site_description`: Descrizione del sito (string)

#### Maintenance
- `maintenance_mode`: Modalità manutenzione (boolean)

#### OAuth Configuration
- `oauth_allowed_domains`: Domini autorizzati per OAuth (array)
- `oauth_enabled`: Abilita OAuth (boolean)
- `oauth_google_client_id`: Client ID Google OAuth (string)
- `oauth_google_client_secret`: Client Secret Google OAuth (string)
- `oauth_google_redirect_url`: URL redirect OAuth (string)

#### Admin Configuration
- `admin_default_email`: Email admin di default (string)
- `admin_registration_enabled`: Abilita registrazione admin (boolean)

#### Application Configuration
- `debug_mode`: Modalità debug (boolean)
- `app_timezone`: Timezone applicazione (string)
- `app_locale`: Lingua applicazione (string)

### 2. Migration Settings

**File:** `database/settings/2025_12_22_144806_create_general_settings.php`

Valori di default:
- `site_name`: "Zuora Workflow"
- `site_description`: "Gestione Workflow Zuora"
- `maintenance_mode`: false
- `oauth_allowed_domains`: []

**File:** `database/settings/2025_12_22_152241_add_setup_fields_to_general_settings.php`

Valori aggiuntivi per sincronizzazione setup:
- `oauth_enabled`: true
- `oauth_google_client_id`: ""
- `oauth_google_client_secret`: ""
- `oauth_google_redirect_url`: "/auth/google/callback"
- `admin_default_email`: "admin@zuora-workflow.com"
- `admin_registration_enabled`: false
- `debug_mode`: false
- `app_timezone`: "UTC"
- `app_locale`: "en"

### 3. Pagina Filament

**File:** `app/Filament/Pages/Settings.php`

Caratteristiche:
- Accessibile solo agli utenti con ruolo `super_admin`
- Raggruppata in "Settings"
- Ordinamento: 99 (ultima nella navigazione)
- Form organizzato in 5 sezioni:
  - Site Information (2 colonne)
  - Maintenance (1 colonna)
  - OAuth Configuration (2 colonne)
  - Admin Configuration (2 colonne)
  - Application Configuration (2 colonne)

### 4. Configurazione

**File:** `config/settings.php`

La classe `GeneralSettings` è registrata nell'array `settings`.

## Sincronizzazione con Setup Esistente

Il sistema è stato sincronizzato con il setup esistente che utilizzava il modello `AppSetting`. È stata eseguita una **migrazione completa** verso Spatie Settings:

### ✅ Migrazione Completata

1. **AppSetting Model Eliminato**: 
   - File `/app/Models/AppSetting.php` rimosso e backupato
   - Tabella `app_settings` non più utilizzata

2. **OAuthService Aggiornato**: 
   - Modificato per usare `GeneralSettings` invece di `AppSetting`
   - Fallback mantenuto verso config Laravel

3. **Setup Page Aggiornato**: 
   - Import modificato da `App\Models\AppSetting` a `App\Settings\GeneralSettings`
   - Funzionalità mantenuta al 100%

### 🔄 Mapping Completo

| Campo Setup | Campo GeneralSettings | Stato Sincronizzazione |
|-------------|----------------------|----------------------|
| oauth_domains | oauth_allowed_domains | ✅ **Completato** |
| (nuovi) | oauth_enabled | ✅ **Completato** |
| (nuovi) | oauth_google_client_id | ✅ **Completato** |
| (nuovi) | oauth_google_client_secret | ✅ **Completato** |
| (nuovi) | oauth_google_redirect_url | ✅ **Completato** |
| (nuovi) | admin_default_email | ✅ **Completato** |
| (nuovi) | admin_registration_enabled | ✅ **Completato** |
| (nuovi) | debug_mode | ✅ **Completato** |
| (nuovi) | app_timezone | ✅ **Completato** |
| (nuovi) | app_locale | ✅ **Completato** |

### 🧪 Test Superato

```php
// Test di integrità
use App\Services\OAuthService;
use App\Settings\GeneralSettings;

$domains = OAuthService::getAllowedDomains(); // ["newdomain.com"]
$settings = app(GeneralSettings::class);
$direct = $settings->oauth_allowed_domains; // ["newdomain.com"]

// ✅ Service and Settings match: true
```

### 🎯 Sistema Unificato

**Ora tutto il sistema usa esclusivamente Spatie Settings:**
- ✅ `GeneralSettings` come fonte di verità unica
- ✅ `OAuthService` integrato con GeneralSettings
- ✅ `Setup` funzionante con GeneralSettings
- ✅ `Settings` page con tutti i campi
- ✅ Backup di AppSetting mantenuto

**Benefici ottenuti:**
- 🏗 **Architettura pulita**: Single source of truth
- 🔒 **Type safety**: Settings fortemente tipizzati
- 📝 **Manutenibilità**: Interfaccia centralizzata
- 🚀 **Performance**: Cache built-in di Spatie Settings
- 🔧 **Debuggabilità**: Migliore tracing dei settings
- 🔄 **Migrations strutturate**: Versioning automatico dei settings

### Modifica delle Impostazioni

```php
use App\Settings\GeneralSettings;

$settings = app(GeneralSettings::class);
$settings->site_name = 'Nuovo Nome';
$settings->oauth_enabled = true;
$settings->save();
```

### Interfaccia Admin

Le impostazioni sono accessibili tramite l'interfaccia Filament:
- URL: `/admin/settings`
- Richiede ruolo: `super_admin`

### Compatibilità con AppSetting

Per mantenere la compatibilità con il codice esistente, il modello `AppSetting` rimane disponibile. Tuttavia, si raccomanda di migrare gradualmente all'uso diretto di `GeneralSettings`.

```php
// Vecchio metodo (ancora funzionante)
$domains = AppSetting::getOAuthDomains();

// Nuovo metodo raccomandato
$settings = app(GeneralSettings::class);
$domains = $settings->oauth_allowed_domains;
```

## Database

### Tabella Settings

La tabella `settings` ha la seguente struttura:

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| group | varchar(255) | Gruppo del setting (es: 'general') |
| name | varchar(255) | Nome del setting |
| payload | longtext | Valore JSON del setting |
| locked | tinyint(1) | Indica se il setting è bloccato |
| created_at | timestamp | Data creazione |
| updated_at | timestamp | Data ultimo aggiornamento |

## Aggiungere Nuovi Settings

### 1. Estendere GeneralSettings

```php
namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ExtendedSettings extends Settings
{
    public string $new_property;
    
    public static function group(): string
    {
        return 'general'; // o 'extended'
    }
}
```

### 2. Creare una migration

```bash
lando php artisan make:settings-migration AddExtendedSettings
```

### 3. Definire i valori di default nella migration

```php
public function up(): void
{
    $this->migrator->add('general.new_property', 'default_value');
}
```

### 4. Registrare la classe in config/settings.php

```php
'settings' => [
    \App\Settings\GeneralSettings::class,
    \App\Settings\ExtendedSettings::class,
],
```

## Migrazione da AppSetting a Spatie Settings

Per migrare completamente da `AppSetting` a `GeneralSettings`:

1. **Identificare tutti i setting utilizzati**
2. **Aggiungere le proprietà corrispondenti a GeneralSettings**
3. **Creare migration con valori di default**
4. **Script di migrazione dati (opzionale)**:

```php
use App\Settings\GeneralSettings;
use App\Models\AppSetting;

// Eseguire una sola volta
$settings = app(GeneralSettings::class);
$settings->oauth_allowed_domains = AppSetting::getOAuthDomains();
$settings->admin_default_email = AppSetting::get('admin_email', 'admin@example.com');
$settings->save();
```

## Cache

Per migliorare le performance, è possibile abilitare la cache nel file `.env`:

```env
SETTINGS_CACHE_ENABLED=true
```

Per pulire la cache dei settings:

```bash
php artisan settings:clear-cache
```

## Testing

```php
use App\Settings\GeneralSettings;

// Fake settings per i test
GeneralSettings::fake([
    'site_name' => 'Test Site',
    'oauth_enabled' => true,
    'app_locale' => 'it',
]);

// Ora i test useranno i valori fake
$settings = app(GeneralSettings::class);
echo $settings->site_name; // 'Test Site'
```

## Note Importanti

1. La tabella `settings` esisteva già nel progetto e viene riutilizzata
2. Ogni modifica richiede una migration
3. I settings sono fortemente tipizzati (type-safe)
4. L'accesso alla pagina è limitato ai super_admin
5. La configurazione supporta l'auto-discovery dei settings
6. **Mantenuta retrocompatibilità** con `AppSetting` per migrazione graduale
7. Tutti i campi del setup sono ora disponibili nell'interfaccia settings

## Riferimenti

- [Documentazione Spatie Laravel Settings](https://github.com/spatie/laravel-settings)
- [Documentazione Filament Spatie Settings Plugin](https://filamentphp.com/plugins/filament-spatie-settings)
- [Documentazione Filament Forms](https://filamentphp.com/docs/forms)
