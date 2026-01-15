<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Concerns\ValidatesZuoraCredentials;
use App\Filament\Resources\Actions\PreviousAction;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    use ValidatesZuoraCredentials;

    protected static string $resource = CustomerResource::class;

    protected function beforeSave(): void
    {
        $data = $this->form->getState();

        // Se il secret non è stato modificato, usa quello esistente
        $clientSecret = $data['zuora_client_secret'] ?: $this->record->zuora_client_secret;

        $this->validateZuoraCredentialsOrHalt(
            $data['zuora_client_id'],
            $clientSecret,
            $data['zuora_base_url']
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            PreviousAction::make(),
        ];
    }
}
