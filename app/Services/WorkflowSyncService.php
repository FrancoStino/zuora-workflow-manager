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
        $stats = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'total' => 0,
            'errors' => [],
        ];

        try {
            $zuoraIds = [];
            $page = 1;
            $hasMore = true;

            while ($hasMore) {
                $data = $this->zuoraService->listWorkflows(
                    $customer->client_id,
                    $customer->client_secret,
                    $customer->base_url,
                    $page,
                    self::PAGE_SIZE
                );

                $workflows = $data['data'] ?? [];
                if (empty($workflows)) {
                    break;
                }

                // Salva/aggiorna ogni workflow e raccogli gli ID Zuora
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

                // Verifica se ci sono altre pagine
                $pagination = $data['pagination'] ?? [];
                $hasMore = isset($pagination['next_page']);
                $page++;
            }

            // Elimina i workflow che non sono più in Zuora
            $stats['deleted'] = $this->deleteStaleWorkflows($customer, $zuoraIds);

            Log::info('Workflow sync completed', [
                'customer_id' => $customer->id,
                'stats' => $stats,
            ]);

        } catch (Exception $e) {
            $stats['errors'][] = $e->getMessage();
            Log::error('Workflow sync failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $stats;
    }

    /**
     * Sincronizza un singolo workflow record
     */
    private function syncWorkflowRecord(Customer $customer, array $workflowData): array
    {
        $zuoraId = $workflowData['id'];

        $workflow = Workflow::firstOrNew(['zuora_id' => $zuoraId, 'customer_id' => $customer->id]);
        $isNew = ! $workflow->exists;

        $workflow->fill([
            'customer_id' => $customer->id,
            'zuora_id' => $zuoraId,
            'name' => $workflowData['name'] ?? 'Unnamed Workflow',
            'description' => $workflowData['description'] ?? null,
            'state' => $workflowData['state'] ?? $workflowData['status'] ?? 'Unknown',
            'created_on' => $workflowData['created_on'] ?? $workflowData['createdAt'] ?? null,
            'updated_on' => $workflowData['updated_on'] ?? $workflowData['updatedAt'] ?? null,
            'last_synced_at' => now(),
        ])->save();

        return [
            'created' => $isNew,
            'updated' => ! $isNew,
        ];
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
}
