<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'user_id' => User::factory(['role' => User::ROLE_PERFORMER]),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => fake()->unique()->slug(4),
            'short_description' => fake()->sentence(16),
            'description' => fake()->paragraphs(3, true),
            'price_from' => fake()->numberBetween(1500, 25000),
            'delivery_days' => fake()->numberBetween(1, 14),
            'status' => Service::STATUS_PUBLISHED,
            'rating' => fake()->randomFloat(2, 4.6, 5.0),
            'reviews_count' => fake()->numberBetween(0, 80),
            'orders_count' => fake()->numberBetween(0, 120),
            'is_featured' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => Service::STATUS_DRAFT,
            'is_featured' => false,
        ]);
    }
}
