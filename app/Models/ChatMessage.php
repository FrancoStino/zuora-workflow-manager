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

    /**
     * Specify attribute casts for the model so `query_results` and `metadata` are treated as arrays.
     *
     * @return array<string,string> Map of attribute names to their cast types.
     */
    protected function casts(): array
    {
        return [
            'query_results' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the chat thread this message belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The related ChatThread relationship.
     */
    public function chatThread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class);
    }

    /**
     * Scope a query to messages matching the given role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     * @param  string  $role  Role to filter by (for example, 'user' or 'assistant').
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder.
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Constrains the query to messages with role 'user'.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     * @return \Illuminate\Database\Eloquent\Builder The modified query builder.
     */
    public function scopeUser($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Filter the query to only include messages with the 'assistant' role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  The query builder instance.
     * @return \Illuminate\Database\Eloquent\Builder The query builder filtered to assistant messages.
     */
    public function scopeAssistant($query)
    {
        return $query->where('role', 'assistant');
    }

    /**
     * Determine whether this message was sent by the user.
     *
     * @return bool `true` if the message role equals 'user', `false` otherwise.
     */
    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Determines whether this message was sent by the assistant.
     *
     * @return bool `true` if the message role equals 'assistant', `false` otherwise.
     */
    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }

    /**
     * Determine whether the message has a generated query.
     *
     * @return bool `true` if the message's `query_generated` attribute is not empty, `false` otherwise.
     */
    public function hasQuery(): bool
    {
        return ! empty($this->query_generated);
    }

    /**
     * Indicates whether this message contains query results.
     *
     * @return bool `true` if `query_results` contains one or more items, `false` otherwise.
     */
    public function hasQueryResults(): bool
    {
        return ! empty($this->query_results);
    }
}
