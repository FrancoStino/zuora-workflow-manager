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
        Schema::table('tasks', function (Blueprint $table) {
            // Dati principali dal JSON Zuora
            $table->string('action_type')->nullable()->after('zuora_id');
            $table->string('object')->nullable()->after('action_type');
            $table->bigInteger('object_id')->nullable()->after('object');
            $table->string('call_type')->nullable()->after('object_id');
            $table->bigInteger('task_id')->nullable()->after('call_type')->comment('Reference to parent task ID');
            $table->string('priority')->default('Medium')->after('task_id');
            $table->integer('concurrent_limit')->default(9999999)->after('priority');

            // Dati complessi salvati come JSON
            $table->json('parameters')->nullable()->after('concurrent_limit');
            $table->json('css')->nullable()->after('parameters');
            $table->json('tags')->nullable()->after('css');
            $table->json('assignment')->nullable()->after('tags');

            // Altri campi Zuora
            $table->string('zuora_org_id')->nullable()->after('assignment');
            $table->json('zuora_org_ids')->nullable()->after('zuora_org_id');
            $table->bigInteger('subprocess_id')->nullable()->after('zuora_org_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'action_type',
                'object',
                'object_id',
                'call_type',
                'task_id',
                'priority',
                'concurrent_limit',
                'parameters',
                'css',
                'tags',
                'assignment',
                'zuora_org_id',
                'zuora_org_ids',
                'subprocess_id',
            ]);
        });
    }
};
