<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\WorkflowSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCustomerWorkflows implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $syncService->syncCustomerWorkflows($this->customer);
    }
}
