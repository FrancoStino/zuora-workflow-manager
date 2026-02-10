<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

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
