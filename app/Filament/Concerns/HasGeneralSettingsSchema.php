<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

trait HasGeneralSettingsSchema
{
    public function getGeneralSettingsSchema(): array
    {
        return [
            //            $this->getSiteInformationSection(),
            $this->getOAuthSection(),
            $this->getMaintenanceSection(),
        ];
    }

    public function getOAuthSection(): Section
    {
        return Section::make('OAuth Configuration')
            ->description('Manage OAuth authentication settings for your application')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->columnSpanFull()
            ->schema($this->getOAuthFields());
    }

    public function getOAuthFields(): array
    {
        return [
            Toggle::make('oauthEnabled')
                ->label('Enable OAuth')
                ->helperText('Enable/disable OAuth authentication')
                ->columnSpanFull()
                ->live(),

            TagsInput::make('oauthAllowedDomains')
                ->columnSpanFull()
                ->label('Allowed Email Domains')
                ->placeholder('Add a domain...')
                ->helperText('Email domains allowed for registration (e.g., example.com). Leave empty to allow all domains.')
                ->separator(',')
                ->reorderable()
                // Ensure it's always an array
                ->dehydrateStateUsing(fn ($state) => is_array($state) ? $state : [])
                ->visible(fn (Get $get) => $get('oauthEnabled')),

            TextInput::make('oauthGoogleClientId')
                ->label('Google Client ID')
                ->required()
                ->placeholder('Enter Google OAuth Client ID or set GOOGLE_CLIENT_ID in .env')
                ->helperText('Get this from Google Cloud Console. Leave empty to use .env GOOGLE_CLIENT_ID')
                // Convert null to empty string on save
                ->dehydrateStateUsing(fn ($state) => $state ?? '')
                ->visible(fn (Get $get) => $get('oauthEnabled')),

            TextInput::make('oauthGoogleClientSecret')
                ->label('Google Client Secret')
                ->required()
                ->password()
                ->revealable()
                ->dehydrateStateUsing(function ($state, $record) {
                    if ($state) {
                        return $state;
                    }

                    return $record?->oauthGoogleClientSecret;
                })
                ->placeholder(fn ($record) => $record ? '***** (già impostato)' : null)
                ->helperText('Get this from Google Cloud Console. Leave empty to use .env GOOGLE_CLIENT_SECRET')
                ->visible(fn (Get $get) => $get('oauthEnabled')),
        ];
    }

    public function getMaintenanceSection(): Section
    {
        return Section::make('Maintenance')
            ->description('Control access to the application during maintenance')
            ->icon(Heroicon::OutlinedWrenchScrewdriver)
            ->columnSpanFull()
            ->schema($this->getMaintenanceFields());
    }

    public function getMaintenanceFields(): array
    {
        return [
            Toggle::make('maintenanceMode')
                ->columnSpanFull()
                ->label('Maintenance Mode')
                ->helperText('When enabled, only administrators can access to the site')
                ->inline(false),
        ];
    }

    public function getSiteInformationSection(): Section
    {
        return Section::make('Site Information')
            ->description('Configure the basic information about your application')
            ->icon(Heroicon::OutlinedInformationCircle)
            ->columnSpanFull()
            ->columns(1)
            ->schema($this->getSiteInformationFields());
    }

    public function getSiteInformationFields(): array
    {
        return [
            TextInput::make('siteName')
                ->label('Site Name')
                ->required()
                ->maxLength(255)
                ->helperText('The name of the application shown in the interface'),

            Textarea::make('siteDescription')
                ->label('Site Description')
                ->required()
                ->rows(3)
                ->maxLength(500)
                ->helperText('A brief description of the application'),
        ];
    }
}
