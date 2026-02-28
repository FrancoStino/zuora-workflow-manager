<?php

namespace App\Settings\Casts;

use App\Support\EncryptionHelper;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;

class EncryptedSettingsCast implements SettingsCast
{
    /**
     * Decrypt the stored payload, returning an empty string when unavailable.
     *
     * @param  mixed  $payload  The stored (possibly encrypted) payload to decrypt.
     * @return string The decrypted value, or an empty string when payload is empty or decryption yields null.
     */
    public function get($payload): mixed
    {
        if (empty($payload)) {
            return '';
        }

        return EncryptionHelper::decrypt($payload) ?? '';
    }

    /**
     * Encrypt a plaintext value for storage.
     *
     * @param  mixed  $payload  The plaintext value to encrypt and store.
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
