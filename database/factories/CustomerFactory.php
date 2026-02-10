<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'zuora_client_id' => 'test_client_'.fake()->uuid(),
            'zuora_client_secret' => 'test_secret_'.fake()->uuid(),
            'zuora_base_url' => 'https://rest.zuora.com',
        ];
    }
}
