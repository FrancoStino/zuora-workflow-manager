# Customer Management Process

## Overview
Customer management handles multi-tenant architecture where each customer has separate Zuora credentials and workflow data.

## Process Flow

### 1. Customer Creation
1. **Initial Setup**
   - Collect customer information and Zuora credentials
   - Validate credential format and connectivity
   - Test API access with provided credentials
   - Create customer record in database

2. **Credential Validation**
   - Test OAuth authentication with Zuora API
   - Verify access to workflow endpoints
   - Check API permissions and rate limits
   - Validate base_url accessibility

3. **Initial Sync**
   - Trigger initial workflow synchronization
   - Validate data retrieval and storage
   - Set up recurring sync schedule
   - Configure monitoring and alerts

### 2. Credential Management
1. **Secure Storage**
   - Encrypt sensitive credential data
   - Use Laravel's encryption for client_secret
   - Implement proper access controls
   - Regular security audits

2. **Credential Rotation**
   - Schedule regular credential updates
   - Implement seamless rotation process
   - Update cached tokens immediately
   - Monitor for rotation failures

3. **Access Control**
   - Role-based access to customer data
   - User permissions per customer
   - Audit trail for credential access
   - Multi-factor authentication for admin access

### 3. Customer Configuration
1. **Sync Settings**
   - Configure sync frequency and timing
   - Set up error handling preferences
   - Define notification preferences
   - Configure performance thresholds

2. **Workflow Preferences**
   - Set workflow filtering criteria
   - Configure export preferences
   - Define data retention policies
   - Set up custom field mappings

3. **Integration Settings**
   - Configure webhook endpoints
   - Set up API access tokens
   - Define integration workflows
   - Configure data sharing preferences

## Database Schema

### Customers Table
```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    client_id VARCHAR(255) NOT NULL,
    client_secret VARCHAR(255) NOT NULL,
    base_url VARCHAR(255) NOT NULL,
    sync_frequency ENUM('hourly', 'daily', 'weekly') DEFAULT 'hourly',
    last_sync_at TIMESTAMP NULL,
    sync_status ENUM('active', 'paused', 'error') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (name),
    INDEX idx_sync_status (sync_status),
    INDEX idx_last_sync (last_sync_at)
);
```

### Customer Settings Table
```sql
CREATE TABLE customer_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    setting_key VARCHAR(255) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_customer_setting (customer_id, setting_key)
);
```

## API Integration

### Customer Resource
```php
class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Customer Name')
                ->required()
                ->maxLength(255),
                
            TextInput::make('client_id')
                ->label('Zuora Client ID')
                ->required()
                ->maxLength(255),
                
            TextInput::make('client_secret')
                ->label('Zuora Client Secret')
                ->required()
                ->password()
                ->maxLength(255),
                
            Select::make('base_url')
                ->label('Zuora Environment')
                ->options([
                    'https://rest.zuora.com' => 'Production',
                    'https://rest.test.zuora.com' => 'Sandbox',
                    'https://rest.apisandbox.zuora.com' => 'API Sandbox',
                ])
                ->required(),
                
            Select::make('sync_frequency')
                ->label('Sync Frequency')
                ->options([
                    'hourly' => 'Every Hour',
                    'daily' => 'Daily',
                    'weekly' => 'Weekly',
                ])
                ->default('hourly'),
        ]);
    }
}
```

### Customer Service
```php
class CustomerService
{
    public function createCustomer(array $data): Customer
    {
        // Validate credentials before creating customer
        $this->validateZuoraCredentials($data);
        
        $customer = Customer::create([
            'name' => $data['name'],
            'client_id' => $data['client_id'],
            'client_secret' => encrypt($data['client_secret']),
            'base_url' => $data['base_url'],
            'sync_frequency' => $data['sync_frequency'] ?? 'hourly',
        ]);
        
        // Trigger initial sync
        SyncCustomerWorkflows::dispatch($customer);
        
        return $customer;
    }
    
    public function validateZuoraCredentials(array $data): bool
    {
        try {
            $zuoraService = new ZuoraService();
            $token = $zuoraService->getAccessToken(
                $data['client_id'],
                $data['client_secret'],
                $data['base_url']
            );
            
            // Test workflow access
            $zuoraService->listWorkflows(
                $data['client_id'],
                $data['client_secret'],
                $data['base_url'],
                1,
                1
            );
            
            return true;
        } catch (Exception $e) {
            throw new InvalidCredentialException(
                'Invalid Zuora credentials: ' . $e->getMessage()
            );
        }
    }
}
```

## Sync Management

### Scheduled Sync
```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->job(new SyncAllCustomersWorkflows())
        ->hourly()
        ->withoutOverlapping()
        ->runInBackground();
}

class SyncAllCustomersWorkflows implements ShouldQueue
{
    public function handle(): void
    {
        Customer::where('sync_status', 'active')
            ->chunk(10, function ($customers) {
                foreach ($customers as $customer) {
                    SyncCustomerWorkflows::dispatch($customer)
                        ->onQueue('workflows')
                        ->delay(now()->addMinutes(rand(1, 30)));
                }
            });
    }
}
```

### Sync Status Tracking
```php
class Customer extends Model
{
    public function updateSyncStatus(string $status, ?string $message = null): void
    {
        $this->update([
            'sync_status' => $status,
            'last_sync_at' => now(),
        ]);
        
        if ($message) {
            $this->settings()->updateOrCreate(
                ['setting_key' => 'last_sync_message'],
                ['setting_value' => $message]
            );
        }
    }
    
    public function getDecryptedClientSecret(): string
    {
        return decrypt($this->client_secret);
    }
}
```

## Performance Optimization

### Bulk Operations
```php
class CustomerBulkService
{
    public function bulkSync(array $customerIds): void
    {
        Customer::whereIn('id', $customerIds)
            ->where('sync_status', 'active')
            ->chunk(5, function ($customers) {
                foreach ($customers as $customer) {
                    SyncCustomerWorkflows::dispatch($customer)
                        ->onQueue('bulk_sync')
                        ->delay(now()->addSeconds(rand(1, 60)));
                }
            });
    }
    
    public function bulkUpdateSettings(array $updates): void
    {
        foreach ($updates as $customerId => $settings) {
            foreach ($settings as $key => $value) {
                CustomerSetting::updateOrCreate(
                    [
                        'customer_id' => $customerId,
                        'setting_key' => $key,
                    ],
                    ['setting_value' => $value]
                );
            }
        }
    }
}
```

### Caching Strategy
```php
class CustomerCacheService
{
    public function getCustomerWorkflows(int $customerId): Collection
    {
        return Cache::remember(
            "customer_workflows_{$customerId}",
            now()->addMinutes(30),
            function () use ($customerId) {
                return Workflow::where('customer_id', $customerId)
                    ->with('customer')
                    ->get();
            }
        );
    }
    
    public function clearCustomerCache(int $customerId): void
    {
        Cache::forget("customer_workflows_{$customerId}");
        Cache::forget("customer_stats_{$customerId}");
    }
}
```

## Security & Compliance

### Data Protection
```php
class CustomerSecurityService
{
    public function encryptSensitiveData(array $data): array
    {
        $sensitiveFields = ['client_secret', 'api_key', 'webhook_secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = encrypt($data[$field]);
            }
        }
        
        return $data;
    }
    
    public function auditCredentialAccess(int $customerId, string $action): void
    {
        CustomerAuditLog::create([
            'customer_id' => $customerId,
            'action' => $action,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### Access Control
```php
class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-any customer');
    }
    
    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('view customer') ||
               $user->customers()->where('customer_id', $customer->id)->exists();
    }
    
    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('update customer');
    }
    
    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('delete customer');
    }
}
```

## Monitoring & Analytics

### Customer Metrics
```php
class CustomerAnalyticsService
{
    public function getCustomerStats(int $customerId): array
    {
        return [
            'total_workflows' => Workflow::where('customer_id', $customerId)->count(),
            'active_workflows' => Workflow::where('customer_id', $customerId)
                ->where('state', 'Active')->count(),
            'last_sync' => Customer::find($customerId)->last_sync_at,
            'sync_success_rate' => $this->calculateSyncSuccessRate($customerId),
            'api_calls_this_month' => $this->getApiCallCount($customerId),
        ];
    }
    
    public function getCustomerHealthScore(int $customerId): float
    {
        $stats = $this->getCustomerStats($customerId);
        
        $score = 0;
        $score += $stats['sync_success_rate'] * 0.4;
        $score += ($stats['active_workflows'] > 0 ? 1 : 0) * 0.2;
        $score += ($stats['last_sync'] && $stats['last_sync']->diffInDays() < 7 ? 1 : 0) * 0.2;
        $score += ($stats['api_calls_this_month'] < 10000 ? 1 : 0) * 0.2;
        
        return $score;
    }
}
```

### Alerting System
```php
class CustomerAlertService
{
    public function checkCustomerHealth(): void
    {
        Customer::where('sync_status', 'active')
            ->chunk(10, function ($customers) {
                foreach ($customers as $customer) {
                    $healthScore = $this->analyticsService->getCustomerHealthScore($customer->id);
                    
                    if ($healthScore < 0.7) {
                        $this->sendHealthAlert($customer, $healthScore);
                    }
                }
            });
    }
    
    private function sendHealthAlert(Customer $customer, float $score): void
    {
        Notification::route('mail', config('app.admin_email'))
            ->notify(new CustomerHealthAlert($customer, $score));
    }
}
```