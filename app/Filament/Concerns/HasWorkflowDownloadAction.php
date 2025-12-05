<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\Workflow;
use Illuminate\Support\Facades\Response;

trait HasWorkflowDownloadAction
{
    /**
     * Crea un'azione di download per il workflow JSON dal database
     */
    protected function createDownloadAction(Workflow $workflow, string $label = 'Download Workflow'): array
    {
        return [
            'label' => $label,
            'icon' => 'heroicon-o-arrow-down-tray',
            'action' => function () use ($workflow) {
                return $this->downloadWorkflowJson($workflow);
            },
            'disabled' => empty($workflow->json_export),
            'tooltip' => empty($workflow->json_export) 
                ? 'No JSON export available for this workflow' 
                : 'Download workflow JSON from database',
        ];
    }

    /**
     * Esegue il download del JSON del workflow
     */
    private function downloadWorkflowJson(Workflow $workflow): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $fileName = "{$workflow->name}.json";
        $content = json_encode($workflow->json_export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return Response::streamDownload(
            function () use ($content) {
                echo $content;
            },
            $fileName,
            [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            ]
        );
    }
}