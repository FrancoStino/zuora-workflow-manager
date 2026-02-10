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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->latestOfMany();
    }

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
