<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('client_id', 'zuora_client_id');
            $table->renameColumn('client_secret', 'zuora_client_secret');
            $table->renameColumn('base_url', 'zuora_base_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('zuora_client_id', 'client_id');
            $table->renameColumn('zuora_client_secret', 'client_secret');
            $table->renameColumn('zuora_base_url', 'base_url');
        });
    }
};
