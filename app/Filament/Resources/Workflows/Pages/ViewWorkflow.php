<?php

namespace App\Filament\Resources\Workflows\Pages;

use App\Filament\Concerns\HasWorkflowDownloadAction;
use App\Filament\Resources\Workflows\RelationManagers\TasksRelationManager;
use App\Filament\Resources\Workflows\WorkflowResource;
use CodebarAg\FilamentJsonField\Infolists\Components\JsonEntry;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Njxqlus\Filament\Components\Infolists\RelationManager;

class ViewWorkflow extends ViewRecord
{
    use HasWorkflowDownloadAction;
    use InteractsWithSchemas;

    protected static string $resource = WorkflowResource::class;

    public function infolist(Schema $schema): Schema
    {
        $this->record->loadMissing(['customer']);

        return $schema
            ->record($this->record)
            ->schema([
                Section::make('General Information')
                    ->description('Basic details about the workflow')
                    ->icon(Heroicon::InformationCircle)
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make([
                            'sm' => 1,
                            'md' => 2,
                            'xl' => 4,
                        ])
                            ->schema([
                                TextEntry::make('zuora_id')
                                    ->label('Workflow ID')
                                    ->icon(Heroicon::Hashtag)
                                    ->copyable(),

                                TextEntry::make('name')
                                    ->label('Workflow Name')
                                    ->icon(Heroicon::DocumentText)
                                    ->copyable(),

                                TextEntry::make('state')
                                    ->label('Status')
                                    ->icon(fn (string $state) => match ($state) {
                                        'Active' => Heroicon::CheckCircle,
                                        'Inactive' => Heroicon::XCircle,
                                        default => Heroicon::QuestionMarkCircle,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'Active' => 'success',
                                        'Inactive' => 'danger',
                                        default => 'gray',
                                    })
                                    ->badge(),
                                TextEntry::make('created_on')
                                    ->label('Created On')
                                    ->icon(Heroicon::Calendar)
                                    ->date('M d, Y'),

                                TextEntry::make('updated_on')
                                    ->label('Last Updated')
                                    ->icon(Heroicon::Clock)
                                    ->date('M d, Y'),

                                TextEntry::make('last_synced_at')
                                    ->label('Last Sync')
                                    ->icon(Heroicon::ArrowPath)
                                    ->formatStateUsing(function ($state) {
                                        if (! $state) {
                                            return 'Never';
                                        }

                                        $daysSince = $this->calculateDaysSinceSync($state);

                                        return $daysSince === 0 ? 'Today' : "$daysSince days ago";
                                    }),
                                TextEntry::make('customer.name')
                                    ->label('Customer Name')
                                    ->icon(Heroicon::BuildingOffice)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),

                                TextEntry::make('id')
                                    ->label('Internal ID')
                                    ->icon(Heroicon::Key)
                                    ->copyable(),

                            ]),
                    ]),

                RelationManager::make()
                    ->columnSpanFull()
                    ->manager(TasksRelationManager::class)
                    ->lazy(),

                Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->contained(false)
                    ->tabs([
                        Tab::make('Workflow Json')
                            ->icon('json')
                            ->schema([
                                ViewEntry::make('copy_json_button')
                                    ->hiddenLabel()
                                    ->view('filament.components.copy-json-button', [
                                        'jsonData' => $this->record->json_export,
                                    ]),

                                JsonEntry::make('json_export')
                                    ->hiddenLabel()
                                    ->darkTheme(),

                            ]),
                        Tab::make('Graphical View')
                            ->icon('workflow-square')
                            ->schema([
                                ViewEntry::make('workflow_graph')
                                    ->hiddenLabel()
                                    ->view('filament.components.workflow-graph', [
                                        'workflowData' => $this->record->json_export,
                                    ]),
                            ]),
                    ]),
            ]);
    }

    private function calculateDaysSinceSync($lastSyncedAt): int
    {
        return (int) abs(now()->diffInDays($lastSyncedAt));
    }

    public function getSubheading(): ?string
    {
        return "Customer: {$this->record->customer->name}";
    }

    public function getTitle(): Htmlable|string
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->view('filament.components.download-workflow-button', [
                    'workflow' => $this->record,
                ]),
        ];
    }
}
