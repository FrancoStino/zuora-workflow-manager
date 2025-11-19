<?php

namespace App\Filament\Resources\WorkflowResource\Pages;

use App\Filament\Resources\WorkflowResource;
use App\Filament\Widgets\WorkflowJsonWidget;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Contracts\Support\Htmlable;

class ViewWorkflow extends ViewRecord
{
    use InteractsWithSchemas;

    protected static string $resource = WorkflowResource::class;

    protected string $view = 'filament.resources.workflow-resource.pages.view-workflow';

    public function workflowInfolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->record)
            ->schema([
                Section::make('General Information')
                    ->description('Basic details about the workflow')
                    ->icon('heroicon-o-information-circle')
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
                                    ->icon('heroicon-o-hashtag')
                                    ->copyable(),

                                TextEntry::make('name')
                                    ->label('Workflow Name')
                                    ->icon('heroicon-o-document-text')
                                    ->copyable(),

                                TextEntry::make('state')
                                    ->label('Status')
                                    ->icon(fn (string $state): string => match ($state) {
                                        'Active' => 'heroicon-o-check-circle',
                                        'Inactive' => 'heroicon-o-x-circle',
                                        default => 'heroicon-o-question-mark-circle',
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'Active' => 'success',
                                        'Inactive' => 'danger',
                                        default => 'gray',
                                    })
                                    ->badge(),
                                TextEntry::make('created_on')
                                    ->label('Created On')
                                    ->icon('heroicon-o-calendar')
                                    ->date('M d, Y'),

                                TextEntry::make('updated_on')
                                    ->label('Last Updated')
                                    ->icon('heroicon-o-clock')
                                    ->date('M d, Y'),

                                TextEntry::make('last_synced_at')
                                    ->label('Last Sync')
                                    ->icon('heroicon-o-arrow-path')
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
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Customer Name')
                                    ->icon('heroicon-o-building-office')
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),
                            ]),

                        Section::make('Technical Details')
                            ->description('System-level information')
                            ->icon('heroicon-o-code-bracket')
                            ->schema([
                                TextEntry::make('id')
                                    ->label('Internal ID')
                                    ->icon('heroicon-o-key')
                                    ->copyable(),

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
                ->label('Download Workflow')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(route('workflow.download', [
                    'customer' => $this->record->customer->name,
                    'workflowId' => $this->record->zuora_id,
                    'name' => $this->record->name,
                ])),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            WorkflowJsonWidget::make([
                'workflow' => $this->record,
            ]),
        ];
    }
}
