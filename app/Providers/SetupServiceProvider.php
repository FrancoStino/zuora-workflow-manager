<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class SetupServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Automatically run migrations if setup tables don't exist
        // This ensures a seamless installation experience for production deployments
        try {
            // Check if we need to run migrations
            if (! Schema::hasTable('setup_completed')) {
                // Run migrations automatically
                \Artisan::call('migrate', ['--force' => true]);

                \Log::info('Setup tables did not exist. Migrations have been run automatically.');
            }
        } catch (\Exception $e) {
            // Silently fail - this could happen during initial Laravel boot
            // or if database connection is not yet configured
        }
    }
}
