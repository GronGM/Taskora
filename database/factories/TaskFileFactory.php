<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskFile>
 */
class TaskFileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(['role' => User::ROLE_CUSTOMER]),
            'original_name' => fake()->word().'.pdf',
            'path' => 'task-files/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10000, 3000000),
        ];
    }
}
