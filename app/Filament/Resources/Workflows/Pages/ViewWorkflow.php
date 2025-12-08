<?php

namespace App\Filament\Resources\Workflows\Pages;

use App\Filament\Concerns\HasWorkflowDownloadAction;
use App\Filament\Resources\Workflows\WorkflowResource;
use CodebarAg\FilamentJsonField\Infolists\Components\JsonEntry;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
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

class ViewWorkflow extends ViewRecord
{
    use HasWorkflowDownloadAction;
    use InteractsWithSchemas;

    protected static string $resource = WorkflowResource::class;

    protected string $view = 'filament.resources.workflow-resource.pages.view-workflow';

    public function workflowInfolist(Schema $schema): Schema
    {
        // Carica solo le relazioni necessarie, evitando query extra
        $this->record->loadMissing(['customer']);

        return $schema
            ->record($this->record)
            ->schema([
                Section::make('General Information')
                    ->description('Basic details about the workflow')
                    ->icon(Heroicon::InformationCircle)
                    ->collapsible()
                    ->schema([
                        Grid::make([
                            'sm' => 1,
                            'md' => 2,
                            'xl' => 3,
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
                            ]),
                    ]),

                Grid::make([
                    'sm' => 1,
                    'md' => 2,
                ])
                    ->schema([

                        Section::make('Customer Information')
                            ->description('Associated customer details')
                            ->icon(Heroicon::UserCircle)
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Customer Name')
                                    ->icon(Heroicon::BuildingOffice)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),
                            ]),

                        Section::make('Technical Details')
                            ->description('System-level information')
                            ->icon(Heroicon::CodeBracket)
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Internal ID')
                                    ->icon(Heroicon::Key)
                                    ->copyable(),

                            ]),
                    ]),
                Tabs::make('Tabs')
                    ->lazy()
                    ->contained(false)
                    ->tabs([
                        Tab::make('Tasks')
                            ->icon(Heroicon::OutlinedRectangleStack)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Under Development'),
                            ]),
                        Tab::make('Workflow Json')
                            ->icon(Heroicon::CodeBracket)
                            ->schema([
                                Action::make('Copy Json')
                                    ->label('Copy JSON')
                                    ->icon(Heroicon::OutlinedClipboardDocument)
                                    ->action(function ($livewire, $record) {
                                        $jsonData = is_string($record->json_export) ? $record->json_export : json_encode($record->json_export);
                                        $livewire->js('navigator.clipboard.writeText('.json_encode($jsonData).');');
                                        Notification::make()
                                            ->success()
                                            ->title('Success')
                                            ->body('JSON copied to clipboard')
                                            ->send();
                                    }),

                                JsonEntry::make('json_export')
                                    ->hiddenLabel()
                                    ->darkTheme(),

                            ]),
                        Tab::make('Graphical View')
                            ->icon(Heroicon::OutlinedChartBar)
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Under Development'),
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
        $actionConfig = $this->createDownloadAction($this->record);

        return [
            Action::make('download')
                ->label($actionConfig['label'])
                ->icon($actionConfig['icon'])
                ->color('primary')
                ->action($actionConfig['action'])
                ->disabled($actionConfig['disabled'])
                ->tooltip($actionConfig['tooltip']),
        ];
    }
}
