<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    // Site Information
    public string $site_name = 'Zuora Workflow';

    public string $site_description = 'Workflow management for Zuora integration';

    // Maintenance
    public bool $maintenance_mode = false;

    // OAuth Configuration preset if empty
    public array $oauth_allowed_domains = [];

    public bool $oauth_enabled = false;

    public string $oauth_google_client_id = '';

    public string $oauth_google_client_secret = '';

    // Admin Configuration
    public string $admin_default_email = 'admin@example.com';

    public static function group(): string
    {
        return 'general';
    }
}
