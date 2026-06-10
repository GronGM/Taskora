<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderMessage>
 */
class OrderMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'type' => OrderMessage::TYPE_USER_MESSAGE,
        ];
    }
}
