<?php

namespace App\Services;

use App\Models\AppSetting;
use Exception;
use Illuminate\Support\Facades\Schema;

class OAuthService
{
    /**
     * Get OAuth allowed domains from database or fallback to config
     */
    public function getAllowedDomains(): array
    {
        try {
            if (Schema::hasTable('app_settings')) {
                $domains = AppSetting::getOAuthDomains();
                if (! empty($domains)) {
                    return $domains;
                }
            }
        } catch (Exception $e) {
            // Silently fail and use fallback
        }

        return config('services.oauth.allowed_domains', []);
    }
}
