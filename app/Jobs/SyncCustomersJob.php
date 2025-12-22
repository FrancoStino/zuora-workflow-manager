<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\WorkflowSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Moox\Jobs\Traits\JobProgress;

class SyncCustomersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    /**
     * Numero di tentativi prima di fallire definitivamente
     */
    public int $tries = 3;

    /**
     * Tempo di attesa tra i tentativi (secondi)
     */
    public int $backoff = 60;

    public function __construct(public Customer $customer) {}

    public function handle(WorkflowSyncService $syncService): void
    {
        // Initialize progress
        $this->setProgress(20);

        // Validate customer exists (might have been deleted after job was queued)
        try {
            $customer = Customer::findOrFail($this->customer->id);
        } catch (ModelNotFoundException) {
            Log::warning('Cannot sync workflows: Customer no longer exists', [
                'customer_id' => $this->customer->id,
            ]);

            // Don't retry - customer was deleted
            $this->delete();

            return;
        }

        $this->setProgress(40);

        // Validate customer has required Zuora credentials
        if (! $this->hasValidCredentials($customer)) {
            Log::error('Cannot sync workflows: Invalid or missing Zuora credentials', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
            ]);

            // Don't retry - credentials are invalid
            $this->delete();

            return;
        }

        $this->setProgress(60);

        // Sync workflows with progress tracking
        $syncService->syncCustomerWorkflows($customer);

        $this->setProgress(100);
    }

    /**
     * Check if customer has all required Zuora credentials
     */
    private function hasValidCredentials(Customer $customer): bool
    {
        return ! empty($customer->zuora_client_id)
            && ! empty($customer->zuora_client_secret)
            && ! empty($customer->zuora_base_url)
            && filter_var($customer->zuora_base_url, FILTER_VALIDATE_URL);
    }
}
