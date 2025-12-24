<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;

trait HasGeneralSettingsSchema
{
    public function getSiteInformationFields(): array
    {
        return [
            TextInput::make('site_name')
                ->label('Site Name')
                ->required()
                ->maxLength(255)
                ->helperText('The name of the application shown in the interface'),

            Textarea::make('site_description')
                ->label('Site Description')
                ->required()
                ->rows(3)
                ->maxLength(500)
                ->helperText('A brief description of the application'),
        ];
    }

    public function getSiteInformationSection(): Fieldset
    {
        return Fieldset::make('Site Information')
            ->schema($this->getSiteInformationFields());
    }

    public function getMaintenanceFields(): array
    {
        return [
            Toggle::make('maintenance_mode')
                ->label('Maintenance Mode')
                ->helperText('When enabled, only administrators can access to the site')
                ->inline(false),
        ];
    }

    public function getMaintenanceSection(): Fieldset
    {
        return Fieldset::make('Maintenance')
            ->schema($this->getMaintenanceFields());
    }

    public function getOAuthFields(): array
    {
        return [
            Toggle::make('oauth_enabled')
                ->label('Enable OAuth')
                ->helperText('Enable/disable OAuth authentication')
                ->live(),

            TagsInput::make('oauth_allowed_domains')
                ->label('Allowed Email Domains')
                ->placeholder('Add a domain...')
                ->helperText('Email domains allowed for registration (e.g., example.com). Leave empty to allow all domains.')
                ->separator(',')
                ->reorderable()
                // Ensure it's always an array
                ->dehydrateStateUsing(fn ($state) => is_array($state) ? $state : [])
                ->visible(fn (Get $get) => $get('oauth_enabled')),

            TextInput::make('oauth_google_client_id')
                ->label('Google Client ID')
                ->placeholder('Enter Google OAuth Client ID or set GOOGLE_CLIENT_ID in .env')
                ->helperText('Get this from Google Cloud Console. Leave empty to use .env GOOGLE_CLIENT_ID')
                // Convert null to empty string on save
                ->dehydrateStateUsing(fn ($state) => $state ?? '')
                ->visible(fn (Get $get) => $get('oauth_enabled')),

            TextInput::make('oauth_google_client_secret')
                ->label('Google Client Secret')
                ->password()
                ->placeholder('Enter Google OAuth Client Secret or set GOOGLE_CLIENT_SECRET in .env')
                ->helperText('Get this from Google Cloud Console. Leave empty to use .env GOOGLE_CLIENT_SECRET')
                // Convert null to empty string on save
                ->dehydrateStateUsing(fn ($state) => $state ?? '')
                ->visible(fn (Get $get) => $get('oauth_enabled')),
        ];
    }

    public function getOAuthSection(): Fieldset
    {
        return Fieldset::make('OAuth Configuration')
            ->schema($this->getOAuthFields());
    }

    public function getApplicationFields(): array
    {
        return [
            TextInput::make('admin_default_email')
                ->label('Admin Default Email')
                ->email()
                ->required()
                ->maxLength(255)
                ->helperText('The default email for the administrator account. To reconfigure the application, visit: '.url('/setup?reset=true')),
        ];
    }

    public function getApplicationSection(): Fieldset
    {
        return Fieldset::make('Application Configuration')
            ->schema($this->getApplicationFields());
    }

    public function getGeneralSettingsSchema(): array
    {
        return [
            $this->getSiteInformationSection(),
            $this->getMaintenanceSection(),
            $this->getOAuthSection(),
            $this->getApplicationSection(),
        ];
    }
}
