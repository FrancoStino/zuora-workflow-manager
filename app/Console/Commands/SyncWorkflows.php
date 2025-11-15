<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\WorkflowSyncService;
use Exception;
use Illuminate\Console\Command;

class SyncWorkflows extends Command
{
    protected $signature = 'app:sync-workflows {--customer=} {--all}';

    protected $description = 'Sync workflows from Zuora API to local database per customer';

    public function handle(WorkflowSyncService $syncService): int
    {
        try {
            if ($this->option('all')) {
                return $this->syncAllCustomers($syncService);
            }

            return $this->syncSingleCustomer($syncService);

        } catch (Exception $e) {
            $this->error('Error syncing workflows: '.$e->getMessage());

            return 1;
        }
    }

    private function syncSingleCustomer(WorkflowSyncService $syncService): int
    {
        $customerName = $this->option('customer');
        if (! $customerName) {
            $this->error('Specify --customer=NAME or use --all');

            return 1;
        }

        $customer = Customer::where('name', $customerName)->firstOrFail();
        $stats = $syncService->syncCustomerWorkflows($customer);
        $this->displayStats($stats, $customer->name);

        return 0;
    }

    private function syncAllCustomers(WorkflowSyncService $syncService): int
    {
        $customers = Customer::all();

        if ($customers->isEmpty()) {
            $this->warn('No customers found.');

            return 0;
        }

        $this->info("Syncing {$customers->count()} customers...\n");

        foreach ($customers as $customer) {
            $this->info("Syncing: {$customer->name}");
            $stats = $syncService->syncCustomerWorkflows($customer);
            $this->displayStats($stats, $customer->name);
        }

        return 0;
    }

    private function displayStats(array $stats, string $customerName): void
    {
        $this->line("<info>Customer: {$customerName}</info>");
        $this->line("  Created: {$stats['created']}");
        $this->line("  Updated: {$stats['updated']}");
        $this->line("  Deleted: {$stats['deleted']}");
        $this->line("  Total processed: {$stats['total']}");

        if (! empty($stats['errors'])) {
            foreach ($stats['errors'] as $error) {
                $this->error("  Error: {$error}");
            }
        }

        $this->line('');
    }
}
