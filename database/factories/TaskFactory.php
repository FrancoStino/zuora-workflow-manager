<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'workflow_id' => Workflow::factory(),
            'task_id' => $this->faker->uuid(),
            'name' => $this->faker->words(2, true).' Task',
            'description' => $this->faker->sentence(),
            'state' => $this->faker->randomElement(['pending', 'running', 'completed', 'failed']),
            'action_type' => $this->faker->randomElement(['Email', 'Export', 'SOAP', 'Callout']),
            'object' => $this->faker->randomElement(['Account', 'Subscription', 'Invoice']),
            'priority' => $this->faker->randomElement(['High', 'Medium', 'Low']),
        ];
    }
}
