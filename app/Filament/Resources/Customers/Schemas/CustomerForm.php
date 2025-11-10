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

                TextInput::make('client_id')
                    ->label('Client ID')
                    ->required()
                    ->maxLength(255),

                TextInput::make('client_secret')
                    ->label('Client Secret')
                    ->required(fn ($context) => $context === 'create')
                    ->password()
                    ->revealable()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn ($state, $record) => $state ?: ($record ? $record->client_secret : null))
                    ->placeholder(fn ($record) => $record ? '***** (già impostato)' : null),

                Select::make('base_url')
                    ->label('Base URL')
                    ->options([
                        'https://rest.zuora.com' => 'https://rest.zuora.com',
                        'https://rest.test.zuora.com' => 'https://rest.test.zuora.com',
                        'https://rest.apisandbox.zuora.com' => 'https://rest.apisandbox.zuora.com',
                    ]),
            ]);
    }
}
