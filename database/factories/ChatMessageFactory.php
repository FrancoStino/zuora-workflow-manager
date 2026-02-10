<?php

namespace Database\Factories;

use App\Models\ChatThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatMessage>
 */
class ChatMessageFactory extends Factory
{
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

    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }

    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'assistant',
        ]);
    }

    public function withQuery(string $query, ?array $results = null): static
    {
        return $this->state(fn (array $attributes) => [
            'query_generated' => $query,
            'query_results' => $results ?? [
                ['id' => 1, 'name' => 'Test Workflow'],
                ['id' => 2, 'name' => 'Another Workflow'],
            ],
        ]);
    }
}
