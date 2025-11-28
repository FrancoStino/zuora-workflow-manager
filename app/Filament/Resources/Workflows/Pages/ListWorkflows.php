<?php

namespace App\Filament\Resources\Workflows\Pages;

use App\Filament\Resources\Workflows\WorkflowResource;
use App\Jobs\SyncCustomerWorkflows;
use App\Models\Customer;
use App\Models\Workflow;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ListWorkflows extends ListRecords
{
    private const DATE_TIME_FORMAT = 'Y-m-d H:i';

    protected static string $resource = WorkflowResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('zuora_id')
                    ->label('Workflow ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('state')
                    ->label('State')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('created_on')
                    ->label('Created')
                    ->dateTime(self::DATE_TIME_FORMAT)
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('updated_on')
                    ->label('Updated')
                    ->dateTime(self::DATE_TIME_FORMAT)
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime(self::DATE_TIME_FORMAT)
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Never'),
            ])
            ->filters([
                SelectFilter::make('customer')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('state')
                    ->label('Workflow State')
                    ->options([
                        'Active' => 'Active',
                        'Inactive' => 'Inactive',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View Details')
                    ->button(),
                Action::make('download')
                    ->label('Download')
                    ->button()
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Workflow $record) => route('workflow.download', [
                        'customer' => $record->customer->name,
                        'workflowId' => $record->zuora_id,
                        'name' => $record->name,
                    ])),
            ])
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50, 100])
            ->persistSearchInSession()
            ->persistColumnSearchesInSession();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_all')
                ->label('Sync All Customers')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sync All Workflows')
                ->modalDescription('This will sync workflows for all customers. Are you sure?')
                ->action(fn () => $this->syncAllWorkflows()),
            Action::make('sync_customer')
                ->label('Sync Customer')
                ->icon('heroicon-o-arrow-path')
                ->modalHeading('Sync Customer Workflows')
                ->modalDescription('Select a customer to synchronize their workflows.')
                ->modalSubmitActionLabel('Sync')
                ->modalWidth('md')
                ->form([
                    Select::make('customer_id')
                        ->label('Select Customer')
                        ->options(Customer::pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $customer = Customer::find($data['customer_id']);
                    if ($customer) {
                        SyncCustomerWorkflows::dispatch($customer);
                        Notification::make()
                            ->title('Synchronization finished')
                            ->body("Synced workflows for {$customer->name}.")
                            ->success()
                            ->send();
                    }
                }),
        ];
    }

    protected function syncAllWorkflows(): void
    {
        Customer::all()->each(function (Customer $customer) {
            SyncCustomerWorkflows::dispatch($customer);
        });

        Notification::make()
            ->title('Synchronization finished')
            ->body('Synced workflows for all customers.')
            ->success()
            ->send();
    }
}
