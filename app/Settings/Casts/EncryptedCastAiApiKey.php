<?php

namespace App\Settings\Casts;

use App\Support\EncryptionHelper;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class EncryptedCastAiApiKey implements SettingsCast
{
    /**
     * Returns the decrypted AI API key stored in settings or an empty string when not available.
     *
     * @param mixed $payload The stored (possibly encrypted) payload to decrypt.
     * @return string The decrypted API key, or an empty string when payload is empty or decryption yields null.
     */
    public function get($payload): mixed
    {
        if (empty($payload)) {
            return '';
        }

        return EncryptionHelper::decrypt($payload) ?? '';
    }

    /**
     * Encrypts a plaintext value for storage and returns the encrypted string.
     *
     * @param mixed $payload The plaintext value to encrypt and store.
     * @return string The encrypted representation, or an empty string if the input is empty or encryption returns null.
     */
    public function set($payload): mixed
    {
        if (empty($payload)) {
            return '';
        }

        return EncryptionHelper::encrypt($payload) ?? '';
    }
}