<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Concerns\ValidatesZuoraCredentials;
use App\Filament\Resources\Actions\PreviousAction;
use App\Filament\Resources\Customers\CustomerResource;
use App\Jobs\SyncCustomersJob;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    use ValidatesZuoraCredentials;

    protected static string $resource = CustomerResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        $this->validateZuoraCredentialsOrHalt(
            $data['zuora_client_id'],
            $data['zuora_client_secret'],
            $data['zuora_base_url']
        );
    }

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
