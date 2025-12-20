<?php

namespace App\Filament\Resources\Workflows\Pages;

use App\Filament\Concerns\HasWorkflowDownloadAction;
use App\Filament\Resources\Workflows\WorkflowResource;
use App\Jobs\SyncCustomerWorkflows;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

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
                ->modalDescription('This will queue sync jobs for all customers. Are you sure?')
                ->action(fn () => $this->syncAllWorkflows()),
            Action::make('sync_customer')
                ->label('Sync Customer')
                ->icon(Heroicon::OutlinedArrowPath)
                ->modalHeading('Sync Customer Workflows')
                ->modalDescription('Select a customer to queue their workflow synchronization.')
                ->modalSubmitActionLabel('Queue Sync')
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
                        SyncCustomerWorkflows::dispatch($customer);

                        Notification::make()
                            ->title('Sync Job Queued')
                            ->body("Workflow sync queued for {$customer->name}.")
                            ->success()
                            ->send();
                    }
                }),
        ];
    }

    protected function syncAllWorkflows(): void
    {
        $customers = Customer::all();
        
        $customers->each(function (Customer $customer) {
            SyncCustomerWorkflows::dispatch($customer);
        });

        Notification::make()
            ->title('Sync Jobs Queued')
            ->body("Queued {$customers->count()} workflow sync jobs. Monitor progress in the Jobs panel.")
            ->success()
            ->send();
    }
}
