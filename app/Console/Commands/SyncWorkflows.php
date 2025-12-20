<?php

namespace App\Console\Commands;

use App\Jobs\SyncCustomerWorkflows;
use App\Models\Customer;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class SyncWorkflows extends Command
{
    protected $signature = 'app:sync-workflows {--customer=} {--all} {--sync : Run synchronously instead of queuing}';

    protected $description = 'Queue workflows sync jobs from Zuora API to local database per customer';

    public function handle(): int
    {
        try {
            if ($this->option('all')) {
                return $this->queueAllCustomers();
            }

            return $this->queueSingleCustomer();

        } catch (Exception $e) {
            $this->error('Error queuing workflow sync: '.$e->getMessage());

            return 1;
        }
    }

    private function queueSingleCustomer(): int
    {
        $customerName = $this->option('customer');
        if (! $customerName) {
            $this->error('Specify --customer=NAME or use --all');

            return 1;
        }

        $customer = Customer::where('name', $customerName)->firstOrFail();

        if ($this->option('sync')) {
            SyncCustomerWorkflows::dispatchSync($customer);
            $this->info("✓ Sync completed for: {$customer->name}");
        } else {
            SyncCustomerWorkflows::dispatch($customer);
            $this->info("✓ Sync job queued for: {$customer->name}");
        }

        return 0;
    }

    private function queueAllCustomers(): int
    {
        // Controlla se ci sono job in coda per evitare sovrapposti
        if (Queue::size() > 0) {
            $this->info('Queue not empty, waiting for current jobs to complete.');

            return 0;
        }

        $customers = Customer::all();

        if ($customers->isEmpty()) {
            $this->warn('No customers found.');

            return 0;
        }

        $this->info("Queuing sync jobs for {$customers->count()} customers...\n");

        foreach ($customers as $customer) {
            if ($this->option('sync')) {
                SyncCustomerWorkflows::dispatchSync($customer);
                $this->info("✓ Sync completed for: {$customer->name}");
            } else {
                SyncCustomerWorkflows::dispatch($customer);
                $this->info("✓ Sync job queued for: {$customer->name}");
            }
        }

        $this->newLine();

        if (! $this->option('sync')) {
            $this->info('All jobs queued successfully. Monitor with:');
            $this->line('  php artisan queue:work');
            $this->line('  php artisan queue:monitor');
        }

        return 0;
    }
}
