<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the chat_threads table.
     *
     * The table contains:
     * - `id` primary key
     * - `user_id` foreign key referencing `users(id)` with cascade on delete
     * - `title` nullable string
     * - `created_at` and `updated_at` timestamps
     * An index is added on `user_id`.
     */
    public function up(): void
    {
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Drop the `chat_threads` table if it exists.
     *
     * Reverses the migration by removing the `chat_threads` table and its indexes/constraints.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
    }
};