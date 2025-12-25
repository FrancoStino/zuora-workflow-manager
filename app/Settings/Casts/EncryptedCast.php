<?php

namespace App\Settings\Casts;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class EncryptedCast implements SettingsCast
{
    /**
     * Get the value from the payload (decrypt from database)
     *
     * @param  mixed  $payload
     */
    public function get($payload): mixed
    {
        // If payload is null or empty, return empty string
        if (empty($payload)) {
            return '';
        }

        try {
            // Decrypt the value
            return Crypt::decryptString($payload);
        } catch (DecryptException $e) {
            // If decryption fails (e.g., data was not encrypted), return as is
            // This handles migration from plain text to encrypted
            return $payload;
        }
    }

    /**
     * Set the value in the payload (encrypt for database)
     *
     * @param  mixed  $payload
     */
    public function set($payload): mixed
    {
        // If payload is null or empty, return empty string
        if (empty($payload)) {
            return '';
        }

        // Encrypt the value before storing
        return Crypt::encryptString($payload);
    }
}
