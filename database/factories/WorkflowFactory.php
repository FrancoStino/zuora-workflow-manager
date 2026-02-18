<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    /**
     * Provide default attribute values for creating a Workflow model instance.
     *
     * The returned array includes keys:
     * - `customer_id`: a related Customer factory,
     * - `zuora_id`: a UUID string,
     * - `name`: three random words suffixed with " Workflow",
     * - `description`: a random sentence,
     * - `state`: one of "Active", "Draft", or "Inactive",
     * - `created_on`: a datetime within the last year,
     * - `updated_on`: a datetime within the last month,
     * - `last_synced_at`: the current timestamp.
     *
     * @return array<string,mixed> Associative array of Workflow attributes for factory creation.
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'zuora_id' => $this->faker->uuid(),
            'name' => $this->faker->words(3, true).' Workflow',
            'description' => $this->faker->sentence(),
            'state' => $this->faker->randomElement(['Active', 'Draft', 'Inactive']),
            'created_on' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_on' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'last_synced_at' => now(),
        ];
    }
}