<?php

namespace App\Services;

use App\Settings\GeneralSettings;
use Exception;
use Illuminate\Support\Facades\Config;

class OAuthService
{
    /**
     * Get OAuth allowed domains from GeneralSettings or fallback to config
     */
    public static function getAllowedDomains(): array
    {
        try {
            $settings = app(GeneralSettings::class);
            $domains = $settings->oauthAllowedDomains ?? [];

            if (! empty($domains)) {
                return $domains;
            }
        } catch (Exception) {
            // Settings not available, fallback to config
        }

        return config('services.oauth.allowed_domains', []);
    }

    /**
     * Get Google OAuth configuration from settings or environment
     */
    public static function getGoogleOAuthConfig(): array
    {
        try {
            $settings = app(GeneralSettings::class);

            return [
                'client_id' => ! empty($settings->oauthGoogleClientId) ? $settings->oauthGoogleClientId : config('services.google.client_id'),
                'client_secret' => ! empty($settings->oauthGoogleClientSecret) ? $settings->oauthGoogleClientSecret : config('services.google.client_secret'),
                'redirect' => url('/oauth/callback/google'), // Always dynamic based on current domain
                'enabled' => $settings->oauthEnabled ?? config('services.google.enabled', false),
                'allowed_domains' => $settings->oauthAllowedDomains ?? config('services.oauth.allowed_domains', []),
            ];
        } catch (Exception $e) {
            // Fallback to config only
            return config('services.google', []);
        }
    }

    /**
     * Check if OAuth is enabled (from settings or env)
     */
    public static function isEnabled(): bool
    {
        try {
            $settings = app(GeneralSettings::class);

            return $settings->oauthEnabled ?? config('services.google.enabled', false);
        } catch (Exception $e) {
            return config('services.google.enabled', false);
        }
    }
}
