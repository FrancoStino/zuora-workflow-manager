<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Renames task columns to be more semantically clear:
     * - zuora_id → task_id (the unique task identifier from Zuora)
     * - task_id → next_task_id (reference to the next task in workflow)
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Rename task_id to next_task_id first (reference to next task)
            $table->renameColumn('task_id', 'next_task_id');

            // Then rename zuora_id to task_id (the actual task identifier)
            $table->renameColumn('zuora_id', 'task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Revert changes
            $table->renameColumn('task_id', 'zuora_id');
            $table->renameColumn('next_task_id', 'task_id');
        });
    }
};
