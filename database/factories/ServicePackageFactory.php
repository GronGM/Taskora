<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServicePackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePackage>
 */
class ServicePackageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'name' => fake()->randomElement(['Базовый', 'Оптимальный', 'Расширенный']),
            'description' => fake()->sentence(14),
            'price' => fake()->numberBetween(1500, 30000),
            'delivery_days' => fake()->numberBetween(1, 14),
            'revisions_count' => fake()->numberBetween(1, 3),
            'sort_order' => fake()->numberBetween(0, 30),
        ];
    }
}
