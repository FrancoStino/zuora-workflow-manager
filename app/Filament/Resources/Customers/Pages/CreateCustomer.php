<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Actions\PreviousAction;
use App\Filament\Resources\Customers\CustomerResource;
use App\Jobs\SyncCustomersJob;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function afterCreate(): void
    {
        // Avvia il sync dei workflow dopo il salvataggio del customer
        SyncCustomersJob::dispatch($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            PreviousAction::make(),
        ];
    }
}
