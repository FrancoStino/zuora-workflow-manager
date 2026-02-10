<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_thread_id',
        'role',
        'content',
        'query_generated',
        'query_results',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'query_results' => 'array',
            'metadata' => 'array',
        ];
    }

    public function chatThread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class);
    }

    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeUser($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeAssistant($query)
    {
        return $query->where('role', 'assistant');
    }

    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }

    public function hasQuery(): bool
    {
        return ! empty($this->query_generated);
    }

    public function hasQueryResults(): bool
    {
        return ! empty($this->query_results);
    }
}
