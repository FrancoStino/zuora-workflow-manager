<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Provide default attributes for a Customer model instance created by the factory.
     *
     * The returned array contains:
     * - 'name': company name string
     * - 'zuora_client_id': client id prefixed with 'test_client_' followed by a UUID
     * - 'zuora_client_secret': secret prefixed with 'test_secret_' followed by a UUID
     * - 'zuora_base_url': base URL for the Zuora API
     *
     * @return array<string, mixed> Map of attribute names to their default values.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'zuora_client_id' => 'test_client_'.fake()->uuid(),
            'zuora_client_secret' => Crypt::encryptString('test_secret_'.fake()->uuid()),
            'zuora_base_url' => 'https://rest.zuora.com',
        ];
    }
}
