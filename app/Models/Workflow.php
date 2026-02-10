<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'zuora_id',
        'name',
        'description',
        'state',
        'created_on',
        'updated_on',
        'last_synced_at',
        'json_export',
    ];

    protected $casts = [
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        'last_synced_at' => 'datetime',
        'json_export' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Sincronizza i task dal JSON export del workflow.
     *
     * Questo metodo è la Single Source of Truth per la sincronizzazione dei task.
     * Viene utilizzato da:
     * - WorkflowSyncService (sync automatico da Zuora)
     * - ViewWorkflow (pulsante "Sync Tasks")
     * - SyncWorkflowTasks command (CLI)
     *
     * @return int Numero di task sincronizzati
     */
    public function syncTasksFromJson(): int
    {
        if (! $this->json_export || ! isset($this->json_export['tasks'])) {
            \Log::info('No tasks in JSON export for workflow', ['workflow_id' => $this->id]);

            return 0;
        }

        $tasksData = $this->json_export['tasks'];
        $syncedTaskIds = [];

        \Log::info('Syncing tasks for workflow', [
            'workflow_id' => $this->id,
            'workflow_name' => $this->name,
            'tasks_count' => count($tasksData),
        ]);

        foreach ($tasksData as $taskData) {
            $taskId = $taskData['id'] ?? null;

            if (! $taskId) {
                \Log::warning('Task without ID found in workflow', [
                    'workflow_id' => $this->id,
                    'task_name' => $taskData['name'] ?? 'Unknown',
                ]);

                continue;
            }

            $syncedTaskIds[] = $taskId;

            try {
                $this->tasks()->updateOrCreate(
                    [
                        'workflow_id' => $this->id,
                        'task_id' => $taskId,
                    ],
                    $this->buildTaskAttributes($taskData)
                );
            } catch (\Exception $e) {
                \Log::error('Error syncing task', [
                    'workflow_id' => $this->id,
                    'task_id' => $taskId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Elimina i task che non sono più nel JSON export (pulizia automatica)
        $deletedCount = $this->tasks()
            ->whereNotIn('task_id', $syncedTaskIds)
            ->delete();

        if ($deletedCount > 0) {
            \Log::info('Deleted stale tasks', [
                'workflow_id' => $this->id,
                'deleted_count' => $deletedCount,
            ]);
        }

        \Log::info('Task sync completed', [
            'workflow_id' => $this->id,
            'synced' => count($syncedTaskIds),
            'deleted' => $deletedCount,
        ]);

        return count($syncedTaskIds);
    }

    /**
     * Costruisce gli attributi del task dal JSON Zuora.
     * Centralizza la logica di mapping per garantire consistenza.
     */
    private function buildTaskAttributes(array $taskData): array
    {
        return [
            'name' => $taskData['name'] ?? 'Unnamed Task',
            'description' => $this->generateTaskDescription($taskData),
            'action_type' => $taskData['action_type'] ?? null,
            'object' => $taskData['object'] ?? null,
            'object_id' => $taskData['object_id'] ?? null,
            'call_type' => $taskData['call_type'] ?? null,
            'next_task_id' => $taskData['task_id'] ?? null,
            'priority' => $taskData['priority'] ?? 'Medium',
            'concurrent_limit' => $taskData['concurrent_limit'] ?? 9999999,
            'parameters' => $taskData['parameters'] ?? null,
            'css' => $taskData['css'] ?? null,
            'tags' => $taskData['tags'] ?? [],
            'assignment' => $taskData['assignment'] ?? [],
            'zuora_org_id' => $taskData['zuora_org_id'] ?? null,
            'zuora_org_ids' => $taskData['zuora_org_ids'] ?? [],
            'subprocess_id' => $taskData['subprocess_id'] ?? null,
            'state' => 'pending', // Default state per task sincronizzati
        ];
    }

    /**
     * Genera una descrizione leggibile per il task
     */
    private function generateTaskDescription(array $taskData): ?string
    {
        $parts = [];

        if (! empty($taskData['action_type'])) {
            $parts[] = "Tipo: {$taskData['action_type']}";
        }

        if (! empty($taskData['object'])) {
            $parts[] = "Oggetto: {$taskData['object']}";
        }

        if (! empty($taskData['call_type'])) {
            $parts[] = "Call: {$taskData['call_type']}";
        }

        // Aggiungi informazioni dai parametri se esistono
        if (! empty($taskData['parameters'])) {
            $params = $taskData['parameters'];

            // Per task Email
            if (isset($params['email']['subject'])) {
                $parts[] = "Subject: {$params['email']['subject']}";
            }

            // Per task Export
            if (isset($params['where_clause'])) {
                $parts[] = 'Where: '.substr($params['where_clause'], 0, 50).'...';
            }
        }

        return ! empty($parts) ? implode(' | ', $parts) : null;
    }
}
