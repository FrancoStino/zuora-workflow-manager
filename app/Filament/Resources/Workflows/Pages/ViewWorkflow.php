<?php

namespace App\Filament\Resources\Workflows\Pages;

use App\Filament\Resources\Workflows\WorkflowResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Phiki\Grammar\Grammar;

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
                Section::make('Workflow Json')
                    ->description('Complete workflow configuration exported from Zuora')
                    ->icon(Heroicon::Bars3CenterLeft)
                    ->collapsible()
                    ->schema([
                        CodeEntry::make('json_export')
                            ->hiddenLabel()
                            ->grammar(Grammar::Json)
                            ->copyable()
                            ->copyMessage('Copied!')
                            ->copyMessageDuration(1500),

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
                ->icon(Heroicon::ArrowDownTray)
                ->color('primary')
                ->url(route('workflow.download', [
                    'customer' => $this->record->customer->name,
                    'workflowId' => $this->record->zuora_id,
                    'name' => $this->record->name,
                ])),
        ];
    }
}
