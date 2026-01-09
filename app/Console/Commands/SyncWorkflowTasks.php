<?php

namespace App\Console\Commands;

use App\Models\Workflow;
use Illuminate\Console\Command;

class SyncWorkflowTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflows:sync-tasks
                            {--workflow-id= : ID specifico del workflow da sincronizzare}
                            {--all : Sincronizza tutti i workflow}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popola la tabella tasks dai dati JSON dei workflow';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Inizio sincronizzazione task dai workflow...');
        $this->newLine();

        $workflows = $this->getWorkflows();

        if ($workflows->isEmpty()) {
            $this->error('❌ Nessun workflow trovato.');

            return self::FAILURE;
        }

        $this->info("📊 Trovati {$workflows->count()} workflow da processare");
        $this->newLine();

        $totalTasks = 0;
        $bar = $this->output->createProgressBar($workflows->count());
        $bar->start();

        foreach ($workflows as $workflow) {
            $tasksCount = $workflow->syncTasksFromJson();
            $totalTasks += $tasksCount;

            $this->newLine();
            $this->line("  ✓ Workflow '{$workflow->name}' (ID: {$workflow->id}): {$tasksCount} task sincronizzati");

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Sincronizzazione completata!');
        $this->table(
            ['Metrica', 'Valore'],
            [
                ['Workflow processati', $workflows->count()],
                ['Task totali sincronizzati', $totalTasks],
                ['Media task per workflow', $workflows->count() > 0 ? round($totalTasks / $workflows->count(), 2) : 0],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Ottieni i workflow da processare in base alle opzioni
     */
    private function getWorkflows()
    {
        $workflowId = $this->option('workflow-id');
        $allOption = $this->option('all');

        if ($workflowId) {
            return $this->getWorkflowById($workflowId);
        }

        if ($allOption || $this->confirm('Do you want to sync all workflows?', true)) {
            return Workflow::whereNotNull('json_export')->get();
        }

        $workflowId = $this->ask('Enter the ID of the workflow to sync');

        return $this->getWorkflowById($workflowId);
    }

    private function getWorkflowById(string|int $workflowId)
    {
        return Workflow::where('id', $workflowId)
            ->whereNotNull('json_export')
            ->get();
    }
}
