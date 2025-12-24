<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    // Site Information
    public string $site_name;

    public string $site_description;

    // Maintenance
    public bool $maintenance_mode;

    // OAuth Configuration
    public array $oauth_allowed_domains;

    public bool $oauth_enabled;

    public string $oauth_google_client_id;

    public string $oauth_google_client_secret;

    public string $oauth_google_redirect_url = '';

    // Admin Configuration
    public string $admin_default_email;

    public static function group(): string
    {
        return 'general';
    }
}
