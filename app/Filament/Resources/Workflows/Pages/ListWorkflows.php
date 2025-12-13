<?php

namespace App\Filament\Resources\Workflows\Pages;

use App\Filament\Concerns\HasWorkflowDownloadAction;
use App\Filament\Resources\Workflows\WorkflowResource;
use App\Models\Customer;
use App\Services\WorkflowSyncService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Log;

class ListWorkflows extends ListRecords
{
    use HasWorkflowDownloadAction;

    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_all')
                ->label('Sync All Customers')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sync All Workflows')
                ->modalDescription('This will sync workflows for all customers. Are you sure?')
                ->action(fn () => $this->syncAllWorkflows()),
            Action::make('sync_customer')
                ->label('Sync Customer')
                ->icon(Heroicon::OutlinedArrowPath)
                ->modalHeading('Sync Customer Workflows')
                ->modalDescription('Select a customer to synchronize their workflows.')
                ->modalSubmitActionLabel('Sync')
                ->modalWidth('md')
                ->schema([
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
                        $syncService = app(WorkflowSyncService::class);
                        $syncService->syncCustomerWorkflows($customer);

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
        // Per ora eseguiamo la sincronizzazione in sync mode invece di usare la coda
        $syncService = app(WorkflowSyncService::class);

        Customer::all()->each(function (Customer $customer) use ($syncService) {
            try {
                $syncService->syncCustomerWorkflows($customer);
            } catch (Exception $e) {
                Log::error('Error syncing customer workflows', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        Notification::make()
            ->title('Synchronization finished')
            ->body('Synced workflows for all customers.')
            ->success()
            ->send();
    }
}
