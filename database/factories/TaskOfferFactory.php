<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskOffer>
 */
class TaskOfferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(['role' => User::ROLE_PERFORMER]),
            'message' => fake()->paragraph(),
            'price' => fake()->numberBetween(1500, 25000),
            'delivery_days' => fake()->numberBetween(1, 14),
            'status' => TaskOffer::STATUS_SUBMITTED,
        ];
    }

    public function withdrawn(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskOffer::STATUS_WITHDRAWN,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskOffer::STATUS_REJECTED,
        ]);
    }
}
