<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Actions\PreviousAction;
use App\Jobs\SyncCustomerWorkflows;
use App\Models\Customer;
use App\Models\Workflow;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerWorkflows extends Page implements HasTable
{
    use InteractsWithTable;

    private const DATE_TIME_FORMAT = 'Y-m-d H:i';

    protected static ?string $slug = 'workflows/{customer}';

    protected static bool $shouldRegisterNavigation = false;

    public string $customer;

    public ?Customer $customerModel = null;

    protected string $view = 'filament.pages.customer-workflows';

    public function mount(string $customer): void
    {
        $this->customer = $customer;
        $this->customerModel = Customer::where('name', $customer)->first();

        if (! $this->customerModel) {
            abort(404, 'Customer not found');
        }
    }

    public function getTitle(): string
    {
        return "Workflows - {$this->customer}";
    }

    public function getHeading(): string
    {
        return "Workflows for {$this->customer}";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('zuora_id')
                    ->label('Workflow ID')
                    ->searchable(true)
                    ->sortable(true),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(true)
                    ->sortable(true),
                TextColumn::make('state')
                    ->label('State')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(true),
                TextColumn::make('created_on')
                    ->label('Created')
                    ->dateTime(self::DATE_TIME_FORMAT)
                    ->sortable(true),
                TextColumn::make('updated_on')
                    ->label('Updated')
                    ->dateTime(self::DATE_TIME_FORMAT)
                    ->sortable(true),
                TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime(self::DATE_TIME_FORMAT)
                    ->sortable(true)
                    ->placeholder('Never'),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label('Workflow State')
                    ->options([
                        'Active' => 'Active',
                        'Inactive' => 'Inactive',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Workflow $record) => ViewWorkflow::getUrl([
                        'customer' => $this->customer,
                        'workflow' => $record->zuora_id,
                    ])),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Workflow $record) => route('workflow.download', [
                        'customer' => $this->customer,
                        'workflowId' => $record->zuora_id,
                        'name' => $record->name,
                    ])),
            ])
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50, 100]);
    }

    protected function getTableQuery(): Builder
    {
        return $this->customerModel->workflows()
            ->getQuery()
            ->select('id', 'zuora_id', 'name', 'state', 'created_on', 'updated_on', 'last_synced_at');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sync Workflows')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->syncWorkflows()),
            PreviousAction::make(),
        ];
    }

    public function syncWorkflows(): void
    {
        SyncCustomerWorkflows::dispatch($this->customerModel);

        Notification::make()
            ->title('Workflows synchronized')
            ->body('Workflows have been synchronized successfully.')
            ->success()
            ->send();
    }
}
