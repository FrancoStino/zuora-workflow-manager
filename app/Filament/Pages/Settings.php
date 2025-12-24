<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasGeneralSettingsSchema;
use App\Settings\GeneralSettings;
use BackedEnum;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Settings extends SettingsPage
{
    use HasGeneralSettingsSchema;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string $settings = GeneralSettings::class;

    protected static ?string $navigationLabel = 'General Settings';

    protected static ?string $title = 'General Settings';

    protected static string|null|UnitEnum $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->getGeneralSettingsSchema());
    }
}
