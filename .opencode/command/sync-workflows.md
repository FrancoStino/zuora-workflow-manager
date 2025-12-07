# Sync Workflows Command

## Description
Synchronize workflows from Zuora API for specific customers or all customers.

## Usage
```bash
/sync-workflows [customer_id] [--force] [--queue=queue_name]
```

## Parameters
- `customer_id` (optional): ID of specific customer to sync. If not provided, syncs all active customers.
- `--force`: Force sync even if recently synced
- `--queue`: Specify queue name (default: workflows)

## Examples
```bash
# Sync all active customers
/sync-workflows

# Sync specific customer
/sync-workflows 123

# Force sync specific customer
/sync-workflows 123 --force

# Sync to high priority queue
/sync-workflows --queue=high-priority
```

## Implementation
```php
<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Jobs\SyncCustomerWorkflows;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncWorkflowsCommand extends Command
{
    protected $signature = 'sync-workflows {customer_id?} {--force : Force sync even if recently synced} {--queue=workflows : Queue name}';
    protected $description = 'Synchronize workflows from Zuora API';

    public function handle(): int
    {
        $customerId = $this->argument('customer_id');
        $force = $this->option('force');
        $queue = $this->option('queue');

        try {
            if ($customerId) {
                return $this->syncCustomer($customerId, $force, $queue);
            } else {
                return $this->syncAllCustomers($force, $queue);
            }
        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");
            Log::error('Workflow sync command failed', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }

    private function syncCustomer(int $customerId, bool $force, string $queue): int
    {
        $customer = Customer::findOrFail($customerId);

        if (!$force && $customer->last_synced_at && $customer->last_synced_at->gt(now()->subHour())) {
            $this->info("Customer {$customer->name} was synced recently. Use --force to override.");
            return 0;
        }

        $this->info("Syncing workflows for customer: {$customer->name}");

        SyncCustomerWorkflows::dispatch($customer)
            ->onQueue($queue)
            ->onConnection('redis');

        $this->info("Sync job dispatched for customer: {$customer->name}");
        return 0;
    }

    private function syncAllCustomers(bool $force, string $queue): int
    {
        $query = Customer::where('sync_status', 'active');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('last_synced_at')
                  ->orWhere('last_synced_at', '<', now()->subHour());
            });
        }

        $customers = $query->get();

        if ($customers->isEmpty()) {
            $this->info('No customers need syncing.');
            return 0;
        }

        $this->info("Dispatching sync jobs for {$customers->count()} customers");

        $customers->each(function ($customer) use ($queue) {
            SyncCustomerWorkflows::dispatch($customer)
                ->onQueue($queue)
                ->onConnection('redis')
                ->delay(now()->addSeconds(rand(1, 60))); // Stagger jobs
        });

        $this->info("Sync jobs dispatched for {$customers->count()} customers");
        return 0;
    }
}
```

## Output Examples

### Success Output
```
Syncing workflows for customer: Acme Corporation
Sync job dispatched for customer: Acme Corporation
```

### Multiple Customers
```
Dispatching sync jobs for 5 customers
Sync jobs dispatched for 5 customers
```

### Force Sync
```
Syncing workflows for customer: Acme Corporation
Sync job dispatched for customer: Acme Corporation
```

### No Sync Needed
```
Customer Acme Corporation was synced recently. Use --force to override.
```

## Error Handling
- Invalid customer ID: Shows error message and exits with code 1
- API connection issues: Logs error and shows user-friendly message
- Queue issues: Falls back to sync queue or shows error
- Permission issues: Shows appropriate error message

## Monitoring
- Logs sync attempts with customer ID and timestamp
- Tracks job dispatch success/failure
- Monitors sync duration and results
- Alerts on repeated failures

## Performance Considerations
- Staggers job dispatches to prevent API rate limiting
- Uses appropriate queue for background processing
- Implements rate limiting for API calls
- Caches customer credentials securely
- Monitors API usage and adjusts timing accordingly