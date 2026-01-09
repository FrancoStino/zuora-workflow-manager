<?php

namespace App\Settings;

use App\Settings\Casts\EncryptedCastGoogleClientSecret;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    // Site Information
    public string $siteName = 'Zuora Workflow';

    public string $siteDescription = 'Workflow management for Zuora integration';

    // Maintenance
    public bool $maintenanceMode = false;

    // OAuth Configuration preset if empty
    public array $oauthAllowedDomains = [];

    public bool $oauthEnabled = false;

    public string $oauthGoogleClientId = '';

    public string $oauthGoogleClientSecret = '';

    public static function group(): string
    {
        return 'general';
    }

    public static function casts(): array
    {
        return [
            'oauthGoogleClientSecret' => EncryptedCastGoogleClientSecret::class,
        ];
    }
}
