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
        Schema::table('workflows', function (Blueprint $table) {
            // Drop the existing global unique constraint on zuora_id
            $table->dropUnique('workflows_zuora_id_unique');

            // Add a composite unique constraint scoped by customer_id
            $table->unique(['customer_id', 'zuora_id'], 'workflows_customer_zuora_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            // Restore the global unique constraint
            $table->dropUnique('workflows_customer_zuora_unique');
            $table->unique('zuora_id', 'workflows_zuora_id_unique');
        });
    }
};
