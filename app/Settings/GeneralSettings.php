<?php

namespace App\Settings;

use App\Settings\Casts\EncryptedCastAiApiKey;
use App\Settings\Casts\EncryptedCastGoogleClientSecret;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $siteName = 'Zuora Workflow';

    public string $siteDescription = 'Workflow management for Zuora integration';

    public bool $maintenanceMode = false;

    public array $oauthAllowedDomains = [];

    public bool $oauthEnabled = false;

    public string $oauthGoogleClientId = '';

    public string $oauthGoogleClientSecret = '';

    public bool $aiChatEnabled = false;

    public string $aiProvider = 'openai';

    public string $aiApiKey = '';

    public string $aiModel = 'gpt-4o-mini';

    public static function group(): string
    {
        return 'general';
    }

    public static function casts(): array
    {
        return [
            'oauthGoogleClientSecret' => EncryptedCastGoogleClientSecret::class,
            'aiApiKey' => EncryptedCastAiApiKey::class,
        ];
    }
}
