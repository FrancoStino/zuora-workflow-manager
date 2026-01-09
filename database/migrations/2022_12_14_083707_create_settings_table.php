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

        DB::table('settings')->insert([
            [
                'group' => 'general',
                'name' => 'siteName',
                'locked' => false,
                'payload' => json_encode('Zuora Workflow'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'siteDescription',
                'locked' => false,
                'payload' => json_encode('Workflow management for Zuora integration'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'maintenanceMode',
                'locked' => false,
                'payload' => json_encode(false),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'oauthAllowedDomains',
                'locked' => false,
                'payload' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'oauthEnabled',
                'locked' => false,
                'payload' => json_encode(false),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'oauthGoogleClientId',
                'locked' => false,
                'payload' => json_encode(''),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'oauthGoogleClientSecret',
                'locked' => false,
                'payload' => json_encode(''),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'general',
                'name' => 'adminDefaultEmail',
                'locked' => false,
                'payload' => json_encode('admin@example.com'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Fix oauthAllowedDomains format if it exists and is corrupted
        $setting = DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'oauthAllowedDomains')
            ->first();

        if ($setting) {
            $payload = json_decode($setting->payload, true);

            // If payload is a string, convert it to an array
            if (is_string($payload)) {
                // Try to decode it again in case it's double-encoded
                $decoded = json_decode($payload, true);
                if (is_array($decoded)) {
                    $newPayload = $decoded;
                } else {
                    // Single domain string, convert to array
                    $newPayload = empty($payload) ? [] : [$payload];
                }

                DB::table('settings')
                    ->where('group', 'general')
                    ->where('name', 'oauthAllowedDomains')
                    ->update([
                        'payload' => json_encode($newPayload),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
