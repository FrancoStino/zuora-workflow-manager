<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('zuora_client_id')
                    ->label('Client ID')
                    ->required()
                    ->maxLength(255),

                TextInput::make('zuora_client_secret')
                    ->label('Client Secret')
                    ->required(fn ($context) => $context === 'create')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn ($state, $record) => $state ?: ($record ? $record->zuora_client_secret : null))
                    ->placeholder(fn ($record) => $record ? '***** (già impostato)' : null),

                Select::make('zuora_base_url')
                    ->label('Base URL')
                    ->required()
                    ->options([
                        'US Developer & Central Sandbox (Applicable for Test Drive and trial access)' => [
                            'https://rest.test.zuora.com' => 'https://rest.test.zuora.com',
                        ],
                        'US API Sandbox' => [
                            'https://rest.sandbox.na.zuora.com' => 'https://rest.sandbox.na.zuora.com (Cloud 1)',
                            'https://rest.apisandbox.zuora.com' => 'https://rest.apisandbox.zuora.com (Cloud 2)',
                        ],
                        'US Production' => [
                            'https://rest.na.zuora.com' => 'https://rest.na.zuora.com (Cloud 1)',
                            'https://rest.api.zuora.com' => 'https://rest.api.zuora.com (Cloud 2)',
                        ],
                        'EU Developer & Central Sandbox' => [
                            'https://rest.test.eu.zuora.com' => 'https://rest.test.eu.zuora.com',
                        ],
                        'EU API Sandbox' => [
                            'https://rest.sandbox.eu.zuora.com' => 'https://rest.sandbox.eu.zuora.com (Cloud 1)',
                        ],
                        'EU Production' => [
                            'https://rest.eu.zuora.com' => 'https://rest.eu.zuora.com',
                        ],
                        'APAC Developer & Central Sandbox' => [
                            'https://rest.test.ap.zuora.com' => 'https://rest.test.ap.zuora.com',
                        ],
                        'APAC Production' => [
                            'https://rest.ap.zuora.com' => 'https://rest.ap.zuora.com',
                        ],
                    ]),
            ]);
    }
}
