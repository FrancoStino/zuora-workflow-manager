<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'json'];

    /**
     * Check if app_settings table exists
     */
    private static function tableExists(): bool
    {
        return Schema::hasTable('app_settings');
    }

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        if (! self::tableExists()) {
            return $default;
        }

        return self::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value): void
    {
        if (! self::tableExists()) {
            return;
        }

        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Get OAuth allowed domains as array
     */
    public static function getOAuthDomains(): array
    {
        return self::get('oauth_allowed_domains', []) ?? [];
    }

    /**
     * Set OAuth allowed domains from array or string
     */
    public static function setOAuthDomains(array|string $domains): void
    {
        $domainArray = is_string($domains)
            ? array_filter(array_map('trim', explode(',', $domains)))
            : array_filter($domains);

        self::set('oauth_allowed_domains', $domainArray);
    }
}
