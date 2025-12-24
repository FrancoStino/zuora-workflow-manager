<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Settings extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string $settings = GeneralSettings::class;

    protected static ?string $navigationLabel = 'General Settings';

    protected static ?string $title = 'General Settings';

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    public function canAccessPanel(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('Site Information')
                    ->schema([
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
                    ]),

                Fieldset::make('Maintenance')
                    ->schema([
                        Toggle::make('maintenance_mode')
                            ->label('Maintenance Mode')
                            ->helperText('When enabled, only administrators can access to the site')
                            ->inline(false),
                    ]),

                Fieldset::make('OAuth Configuration')
                    ->schema([
                        TagsInput::make('oauth_allowed_domains')
                            ->label('Allowed Email Domains')
                            ->placeholder('Add a domain...')
                            ->helperText('Email domains allowed for registration (e.g., example.com). Leave empty to allow all domains.')
                            ->separator(',')
                            ->splitKeys(['Tab', 'Enter', ',', ' '])
                            ->reorderable(),

                        Toggle::make('oauth_enabled')
                            ->label('Enable OAuth')
                            ->helperText('Enable/disable OAuth authentication'),

                        TextInput::make('oauth_google_client_id')
                            ->label('Google Client ID')
                            ->placeholder('Enter Google OAuth Client ID or set GOOGLE_CLIENT_ID in .env')
                            ->helperText('Get this from Google Cloud Console. Leave empty to use .env GOOGLE_CLIENT_ID'),

                        TextInput::make('oauth_google_client_secret')
                            ->label('Google Client Secret')
                            ->password()
                            ->placeholder('Enter Google OAuth Client Secret or set GOOGLE_CLIENT_SECRET in .env')
                            ->helperText('Get this from Google Cloud Console. Leave empty to use .env GOOGLE_CLIENT_SECRET'),

                        TextInput::make('oauth_google_redirect_url')
                            ->label('Redirect URL')
                            ->helperText('OAuth callback URL. Leave empty to use .env GOOGLE_REDIRECT_URI'),
                    ]),

                Fieldset::make('Application Configuration')
                    ->schema([
                        TextInput::make('admin_default_email')
                            ->label('Admin Default Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->helperText('The default email for the administrator account. To reconfigure the application, visit: ' . url('/setup?reset=true')),
                    ]),
            ]);
    }
}
