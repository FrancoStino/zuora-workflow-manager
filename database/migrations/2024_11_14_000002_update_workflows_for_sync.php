<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            // Aggiungi customer_id se non esiste
            if (!Schema::hasColumn('workflows', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            }

            // Aggiungi le colonne mancanti se non esistono
            if (!Schema::hasColumn('workflows', 'zuora_id')) {
                $table->string('zuora_id')->unique()->nullable();
            }
            
            if (!Schema::hasColumn('workflows', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable();
            }

            // Assicura che il customer_id sia indexed
            if (!Schema::hasColumn('workflows', 'customer_id')) {
                $table->index('customer_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workflows', function (Blueprint $table) {
            if (Schema::hasColumn('workflows', 'customer_id')) {
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('workflows', 'zuora_id')) {
                $table->dropColumn('zuora_id');
            }
            if (Schema::hasColumn('workflows', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }
        });
    }
};
