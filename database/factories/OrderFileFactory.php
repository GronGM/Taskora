<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderFile>
 */
class OrderFileFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->slug().'.txt';

        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'original_name' => $name,
            'stored_name' => $name,
            'path' => 'orders/'.fake()->numberBetween(1, 100).'/'.$name,
            'disk' => 'local',
            'mime_type' => 'text/plain',
            'size' => fake()->numberBetween(100, 5000),
            'status' => OrderFile::STATUS_AVAILABLE,
            'moderation_status' => OrderFile::MODERATION_CLEAN,
        ];
    }
}
