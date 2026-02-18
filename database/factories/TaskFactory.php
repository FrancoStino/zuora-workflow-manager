<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Provide the default attribute set used to create a Task model instance.
     *
     * The returned array contains keys and their generated values for seeding:
     * - `workflow_id`: a Workflow factory instance to associate a workflow.
     * - `task_id`: a UUID string.
     * - `name`: a two-word name with the suffix " Task".
     * - `description`: a sentence describing the task.
     * - `state`: one of `pending`, `running`, `completed`, or `failed`.
     * - `action_type`: one of `Email`, `Export`, `SOAP`, or `Callout`.
     * - `object`: one of `Account`, `Subscription`, or `Invoice`.
     * - `priority`: one of `High`, `Medium`, or `Low`.
     *
     * @return array<string, mixed> Associative array of default Task attributes.
     */
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