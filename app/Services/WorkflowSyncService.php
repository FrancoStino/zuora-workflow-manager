<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Workflow;
use Exception;
use Illuminate\Support\Facades\Log;

class WorkflowSyncService
{
    private const PAGE_SIZE = 50;

    public function __construct(private ZuoraService $zuoraService) {}

    /**
     * Sincronizza tutti i workflow di un customer dal database locale
     */
    public function syncCustomerWorkflows(Customer $customer): array
    {
        $stats = $this->initializeStats();

        if (! $this->validateCustomerCredentials($customer)) {
            return $this->handleInvalidCredentials($customer, $stats);
        }

        try {
            $zuoraIds = $this->fetchAndSyncAllWorkflows($customer, $stats);
            $stats['deleted'] = $this->deleteStaleWorkflows($customer, $zuoraIds);

            $this->logSyncSuccess($customer, $stats);
        } catch (Exception $e) {
            $this->handleSyncError($customer, $stats, $e);
        }

        return $stats;
    }

    private function initializeStats(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'total' => 0,
            'errors' => [],
        ];
    }

    private function handleInvalidCredentials(Customer $customer, array &$stats): array
    {
        $errorMsg = 'Customer has invalid or missing Zuora credentials';
        $stats['errors'][] = $errorMsg;

        Log::error('Workflow sync aborted: Invalid credentials', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'has_client_id' => ! empty($customer->zuora_client_id),
            'has_client_secret' => ! empty($customer->zuora_client_secret),
            'has_base_url' => ! empty($customer->zuora_base_url),
            'base_url_valid' => filter_var($customer->zuora_base_url, FILTER_VALIDATE_URL) !== false,
        ]);

        return $stats;
    }

    private function fetchAndSyncAllWorkflows(Customer $customer, array &$stats): array
    {
        $zuoraIds = [];
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $data = $this->zuoraService->listWorkflows(
                $customer->zuora_client_id,
                $customer->zuora_client_secret,
                $customer->zuora_base_url,
                $page,
                self::PAGE_SIZE
            );

            $workflows = $data['data'] ?? [];
            if (empty($workflows)) {
                break;
            }

            $this->processWorkflowBatch($customer, $workflows, $zuoraIds, $stats);

            $pagination = $data['pagination'] ?? [];
            $hasMore = isset($pagination['next_page']);
            $page++;
        }

        return $zuoraIds;
    }

    private function processWorkflowBatch(Customer $customer, array $workflows, array &$zuoraIds, array &$stats): void
    {
        foreach ($workflows as $workflowData) {
            $zuoraId = $workflowData['id'] ?? null;
            if (! $zuoraId) {
                continue;
            }

            $zuoraIds[] = $zuoraId;
            $result = $this->syncWorkflowRecord($customer, $workflowData);

            $stats['created'] += $result['created'] ? 1 : 0;
            $stats['updated'] += $result['updated'] ? 1 : 0;
        }

        $stats['total'] += count($workflows);
    }

    private function logSyncSuccess(Customer $customer, array $stats): void
    {
        Log::info('Workflow sync completed', [
            'customer_id' => $customer->id,
            'stats' => $stats,
        ]);
    }

    private function handleSyncError(Customer $customer, array &$stats, Exception $e): void
    {
        $stats['errors'][] = $e->getMessage();
        Log::error('Workflow sync failed', [
            'customer_id' => $customer->id,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Sincronizza un singolo workflow record
     */
    private function syncWorkflowRecord(Customer $customer, array $workflowData): array
    {
        $zuoraId = $workflowData['id'];

        $workflow = Workflow::firstOrNew(['zuora_id' => $zuoraId, 'customer_id' => $customer->id]);
        $isNew = ! $workflow->exists;

        // Scarica il JSON export del workflow usando il servizio esistente
        $jsonExport = $this->downloadWorkflowJson($customer, $zuoraId);

        $workflow->fill([
            'customer_id' => $customer->id,
            'zuora_id' => $zuoraId,
            'name' => $workflowData['name'] ?? 'Unnamed Workflow',
            'description' => $workflowData['description'] ?? null,
            'state' => $workflowData['state'] ?? $workflowData['status'] ?? 'Unknown',
            'created_on' => $workflowData['created_on'] ?? $workflowData['createdAt'] ?? null,
            'updated_on' => $workflowData['updated_on'] ?? $workflowData['updatedAt'] ?? null,
            'last_synced_at' => now(),
            'json_export' => $jsonExport,
        ])->save();

        // Delega la sincronizzazione dei tasks al Model (Single Responsibility Principle)
        $workflow->syncTasksFromJson();

        return [
            'created' => $isNew,
            'updated' => ! $isNew,
        ];
    }

    /**
     * Scarica il JSON export di un workflow specifico
     * Riutilizza la logica esistente dal ZuoraService
     */
    private function downloadWorkflowJson(Customer $customer, string|int $workflowId): ?array
    {
        try {
            return $this->zuoraService->downloadWorkflow(
                $customer->zuora_client_id,
                $customer->zuora_client_secret,
                $customer->zuora_base_url,
                $workflowId
            );
        } catch (Exception $e) {
            Log::warning('Failed to download workflow JSON', [
                'customer_id' => $customer->id,
                'workflow_id' => $workflowId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Elimina i workflow locali che non sono più presenti in Zuora
     */
    private function deleteStaleWorkflows(Customer $customer, array $zuoraIds): int
    {
        return $customer->workflows()
            ->whereNotIn('zuora_id', $zuoraIds)
            ->delete();
    }

    /**
     * Validate that customer has all required Zuora credentials
     */
    private function validateCustomerCredentials(Customer $customer): bool
    {
        // Check all required fields are present
        if (empty($customer->zuora_client_id)
            || empty($customer->zuora_client_secret)
            || empty($customer->zuora_base_url)
        ) {
            return false;
        }

        // Validate base URL format
        if (filter_var($customer->zuora_base_url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return true;
    }
}
