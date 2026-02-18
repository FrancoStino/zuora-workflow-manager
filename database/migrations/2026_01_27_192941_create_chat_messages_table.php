<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the chat_messages database table with its columns, foreign key constraint, and index.
     *
     * The table includes:
     * - id primary key
     * - chat_thread_id foreign key constrained to chat_threads with cascade on delete
     * - role (string; e.g., "user", "assistant", "system")
     * - content (text)
     * - query_generated (text, nullable)
     * - query_results (json, nullable)
     * - metadata (json, nullable)
     * - created_at and updated_at timestamps
     *
     * Adds a composite index on (chat_thread_id, created_at).
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_thread_id')->constrained()->cascadeOnDelete();
            $table->string('role'); // user, assistant, system
            $table->text('content');
            $table->text('query_generated')->nullable();
            $table->json('query_results')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['chat_thread_id', 'created_at']);
        });
    }

    /**
     * Drop the chat_messages table if it exists.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};