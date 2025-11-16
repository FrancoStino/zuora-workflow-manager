<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Actions\PreviousAction;
use App\Models\Customer;
use App\Models\Workflow;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewWorkflow extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected static ?string $slug = 'workflows/{customer}/{workflow}';

    protected static bool $shouldRegisterNavigation = false;

    public string $customer;

    public string $workflow;

    public ?Customer $customerModel = null;

    public ?Workflow $workflowModel = null;

    protected string $view = 'filament.pages.view-workflow';

    public function mount(string $customer, string $workflow): void
    {
        $this->customer = $customer;
        $this->workflow = $workflow;

        $this->customerModel = Customer::where('name', $customer)->first();

        if (! $this->customerModel) {
            abort(404, 'Customer not found');
        }

        $this->workflowModel = Workflow::where('customer_id', $this->customerModel->id)
            ->where('zuora_id', $workflow)
            ->first();

        if (! $this->workflowModel) {
            abort(404, 'Workflow not found');
        }
    }

    public function getTitle(): string
    {
        return "Workflow - {$this->workflowModel->name}";
    }

    public function getHeading(): string
    {
        return $this->workflowModel->name;
    }

    public function getSubheading(): ?string
    {
        return "Customer: {$this->customer}";
    }

    public function workflowInfolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->workflowModel)
            ->components([
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
                                    ->formatStateUsing(fn ($state) => $state ? ($this->calculateDaysSinceSync($state) === 0 ? 'Today' : $this->calculateDaysSinceSync($state).' days ago') : 'Never'),
                            ]),
                    ]),

                Grid::make([
                    'sm' => 1,
                    'md' => 2,
                ])
                    ->schema([
                        Section::make('Timeline')
                            ->description('Creation and modification timestamps')
                            ->icon('heroicon-o-clock')
                            ->collapsible()
                            ->compact()
                            ->schema([
                                TextEntry::make('created_on')
                                    ->label('Created On')
                                    ->icon('heroicon-o-calendar')
                                    ->dateTime(self::DATE_TIME_FORMAT)
                                    ->placeholder('Not available'),

                                TextEntry::make('updated_on')
                                    ->label('Last Updated')
                                    ->icon('heroicon-o-calendar')
                                    ->dateTime(self::DATE_TIME_FORMAT)
                                    ->placeholder('Not available'),

                                TextEntry::make('last_synced_at')
                                    ->label('Last Synced')
                                    ->icon('heroicon-o-arrow-path')
                                    ->dateTime(self::DATE_TIME_FORMAT)
                                    ->placeholder('Never synchronized')
                                    ->color(fn ($state) => $state ? 'success' : 'warning'),
                            ]),

                        Section::make('Customer Information')
                            ->description('Associated customer details')
                            ->icon('heroicon-o-user-circle')
                            ->collapsible()
                            ->compact()
                            ->schema([
                                TextEntry::make('customer.name')
                                    ->label('Customer Name')
                                    ->icon('heroicon-o-building-office')
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),

                                TextEntry::make('customer.zuora_id')
                                    ->label('Customer Zuora ID')
                                    ->icon('heroicon-o-hashtag')
                                    ->copyable()
                                    ->placeholder('Not available'),

                                TextEntry::make('customer_id')
                                    ->label('Database ID')
                                    ->icon('heroicon-o-key')
                                    ->placeholder('Not available'),
                            ]),
                    ]),

                //                Section::make('Metadata')
                //                    ->description('Additional workflow information')
                //                    ->icon('heroicon-o-cube')
                //                    ->collapsible()
                //                    ->collapsed()
                //                    ->schema([
                //                        KeyValueEntry::make('meta_data')
                //                            ->label('')
                //                            ->hiddenLabel()
                //                            ->placeholder('No metadata available')
                //                            ->columnSpanFull(),
                //                    ]),

                Section::make('Technical Details')
                    ->description('System-level information')
                    ->icon('heroicon-o-code-bracket')
                    ->collapsible()
                    ->collapsed()
                    ->columns([
                        'sm' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextEntry::make('id')
                            ->label('Internal ID')
                            ->icon('heroicon-o-key')
                            ->copyable(),

                        TextEntry::make('created_at')
                            ->label('Record Created')
                            ->icon('heroicon-o-calendar-days')
                            ->dateTime(self::DATE_TIME_FORMAT),

                        TextEntry::make('updated_at')
                            ->label('Record Updated')
                            ->icon('heroicon-o-calendar-days')
                            ->dateTime(self::DATE_TIME_FORMAT),

                        TextEntry::make('deleted_at')
                            ->label('Deleted At')
                            ->icon('heroicon-o-trash')
                            ->dateTime(self::DATE_TIME_FORMAT)
                            ->placeholder('Active')
                            ->color(fn ($state) => $state ? 'danger' : 'success'),
                    ]),
            ]);
    }

    private function calculateDaysSinceSync($lastSyncedAt): int
    {
        return intval(abs(now()->diffInDays($lastSyncedAt)));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label('Download Workflow')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(route('workflow.download', [
                    'customer' => $this->customer,
                    'workflowId' => $this->workflowModel->zuora_id,
                    'name' => $this->workflowModel->name,
                ])),

            Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn () => auth()->user()->can('update', $this->workflowModel))
                ->url(fn () => route('filament.admin.pages.workflows', [
                    'customer' => $this->customer,
                ])),

            PreviousAction::make(),
        ];
    }

    private function calculateDaysSinceUpdate(): int
    {
        return intval(abs(now()->diffInDays($this->workflowModel->updated_on)));
    }
}
