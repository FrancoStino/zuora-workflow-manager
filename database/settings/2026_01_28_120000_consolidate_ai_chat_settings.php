<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Ensures consolidated AI-related general settings exist and creates them with defaults if missing.
     *
     * Adds the following settings only when they are not already present:
     * - `general.aiChatEnabled` => `false`
     * - `general.aiProvider` => `'openai'`
     * - `general.aiApiKey` => `''` (empty string)
     * - `general.aiModel` => `'gpt-4o-mini'`
     */
    public function up(): void
    {
        // Add final consolidated settings if they don't exist
        if (! $this->migrator->exists('general.aiChatEnabled')) {
            $this->migrator->add('general.aiChatEnabled', false);
        }

        if (! $this->migrator->exists('general.aiProvider')) {
            $this->migrator->add('general.aiProvider', 'openai');
        }

        if (! $this->migrator->exists('general.aiApiKey')) {
            $this->migrator->add('general.aiApiKey', '');
        }

        if (! $this->migrator->exists('general.aiModel')) {
            $this->migrator->add('general.aiModel', 'gpt-4o-mini');
        }
    }
};