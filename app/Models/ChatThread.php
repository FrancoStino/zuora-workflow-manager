<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChatThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
    ];

    /**
     * Get the user that owns the chat thread.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The associated User model relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get chat messages for this thread ordered by creation time ascending.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany HasMany relation of ChatMessage models ordered by `created_at` ascending.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the latest related chat message for this thread.
     *
     * Defines a HasMany relationship limited to the most recently created ChatMessage.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany The relationship containing the most recent ChatMessage.
     */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->latestOfMany();
    }

    /**
     * Set the thread title from the first user message when no title is present.
     *
     * If the chat thread has no title, finds the first related message with role "user"
     * and updates the thread's title to that message's content truncated to 50 characters.
     */
    public function generateTitleFromFirstMessage(): void
    {
        if ($this->title) {
            return;
        }

        $firstUserMessage = $this->messages()
            ->where('role', 'user')
            ->first();

        if ($firstUserMessage) {
            $this->update([
                'title' => Str::limit($firstUserMessage->content, 50),
            ]);
        }
    }
}