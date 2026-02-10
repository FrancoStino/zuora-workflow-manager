<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
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
