<?php

namespace Database\Factories;

use App\Models\ChatThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    /**
     * Define default attributes for a ChatMessage factory.
     *
     * Provides default values used when creating a ChatMessage: an associated chat thread via ChatThread::factory(), a role chosen from 'user' or 'assistant', generated paragraph content, and null defaults for `query_generated`, `query_results`, and `metadata`.
     *
     * @return array The attribute map to seed a ChatMessage model.
     */
    public function definition(): array
    {
        return [
            'chat_thread_id' => ChatThread::factory(),
            'role' => fake()->randomElement(['user', 'assistant']),
            'content' => fake()->paragraph(),
            'query_generated' => null,
            'query_results' => null,
            'metadata' => null,
        ];
    }

    /**
     * Configure the factory to produce a ChatMessage with the role set to "user".
     *
     * @return static The factory instance with the 'role' attribute set to `user`.
     */
    public function user(): static
    {
        return $this->state(fn (array $_attributes) => [
            'role' => 'user',
        ]);
    }

    /**
     * Configure the factory to produce a ChatMessage with the role set to "assistant".
     *
     * @return static A factory instance with the `role` attribute set to `assistant`.
     */
    public function assistant(): static
    {
        return $this->state(fn (array $_attributes) => [
            'role' => 'assistant',
        ]);
    }

    /**
     * Attach a query and its results to the factory state for the generated ChatMessage.
     *
     * @param  string  $query  The query string to set on `query_generated`.
     * @param  array|null  $results  Optional array of result records to set on `query_results`. If null, a default two-item sample array is used.
     * @return static The factory instance with the query-related state applied.
     */
    public function withQuery(string $query, ?array $results = null): static
    {
        return $this->state(fn (array $_attributes) => [
            'query_generated' => $query,
            'query_results' => $results ?? [
                ['id' => 1, 'name' => 'Test Workflow'],
                ['id' => 2, 'name' => 'Another Workflow'],
            ],
        ]);
    }
}
