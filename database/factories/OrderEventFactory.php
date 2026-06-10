<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderEvent>
 */
class OrderEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'type' => OrderEvent::TYPE_ORDER_CREATED,
            'payload' => null,
        ];
    }
}
