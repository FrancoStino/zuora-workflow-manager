<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\Workflow;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HasWorkflowDownloadAction
{
    /**
     * Crea un'azione di download per il workflow JSON dal database
     */
    protected function createDownloadAction(Workflow $workflow, string $label = 'Download Workflow'): array
    {
        return [
            'label' => $label,
            'icon' => Heroicon::OutlinedArrowDownTray,
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
    private function downloadWorkflowJson(Workflow $workflow): StreamedResponse
    {
        // Early return se non c'è JSON export
        if (empty($workflow->json_export)) {
            abort(404, 'No JSON export available for this workflow');
        }

        $fileName = "{$workflow->name}.json";

        // Pre-calculate JSON per evitare ricalcoli
        static $jsonCache = [];
        $cacheKey = $workflow->id;

        if (! isset($jsonCache[$cacheKey])) {
            $jsonCache[$cacheKey] = json_encode($workflow->json_export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        $content = $jsonCache[$cacheKey];

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
