<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the global unique constraint on `zuora_id` with a composite unique constraint scoped by `customer_id`.
     *
     * Drops the unique index named `workflows_zuora_id_unique` on the `workflows` table and adds a composite unique index on (`customer_id`, `zuora_id`) named `workflows_customer_zuora_unique`.
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
     * Reverts the unique-constraint changes on the workflows table.
     *
     * Drops the composite unique constraint on (customer_id, zuora_id) named
     * workflows_customer_zuora_unique and restores the unique constraint on zuora_id
     * named workflows_zuora_id_unique.
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