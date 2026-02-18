<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChatThread>
 */
class ChatThreadFactory extends Factory
{
    /**
     * Define the default attributes for a ChatThread model instance.
     *
     * The returned array contains attribute values used when creating a ChatThread:
     * - `user_id`: a User factory instance to associate an owner.
     * - `title`: a randomly generated four-word sentence.
     *
     * @return array<string,mixed> Attribute name => value pairs for the model.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
        ];
    }
}