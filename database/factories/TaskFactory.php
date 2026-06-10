<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        $budgetMin = fake()->numberBetween(1500, 15000);

        return [
            'user_id' => User::factory(['role' => User::ROLE_CUSTOMER]),
            'category_id' => Category::factory(),
            'title' => fake()->unique()->sentence(5),
            'slug' => fake()->unique()->slug(5),
            'description' => fake()->paragraphs(3, true),
            'budget_min' => $budgetMin,
            'budget_max' => $budgetMin + fake()->numberBetween(1000, 20000),
            'deadline_at' => now()->addDays(fake()->numberBetween(3, 21))->toDateString(),
            'status' => Task::STATUS_PUBLISHED,
            'offers_count' => 0,
            'views_count' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => Task::STATUS_DRAFT,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => Task::STATUS_ARCHIVED,
        ]);
    }
}
