<?php

namespace App\Settings\Casts;

use App\Support\EncryptionHelper;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class EncryptedCastAiApiKey implements SettingsCast
{
    public function get($payload): mixed
    {
        if (empty($payload)) {
            return '';
        }

        return EncryptionHelper::decrypt($payload) ?? '';
    }

    public function set($payload): mixed
    {
        if (empty($payload)) {
            return '';
        }

        return EncryptionHelper::encrypt($payload) ?? '';
    }
}
