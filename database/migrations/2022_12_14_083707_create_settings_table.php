<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();

            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->json('payload');

            $table->timestamps();

            $table->unique(['group', 'name']);
        });

        // Insert default general settings
        $settings = [
            [
                'group' => 'general',
                'name' => 'site_name',
                'payload' => json_encode('Zuora Workflow'),
                'locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'site_description',
                'payload' => json_encode('Workflow management for Zuora integration'),
                'locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'maintenance_mode',
                'payload' => json_encode(false),
                'locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'oauth_allowed_domains',
                'payload' => json_encode(['example.com']),
                'locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'oauth_enabled',
                'payload' => json_encode(true),
                'locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'oauth_google_client_id',
                'payload' => json_encode(''),
                'locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'oauth_google_client_secret',
                'payload' => json_encode(''),
                'locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'oauth_google_redirect_url',
                'payload' => json_encode(url('/oauth/callback/google')),
                'locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'admin_default_email',
                'payload' => json_encode('admin@example.com'),
                'locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('settings')->insert($settings);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
