<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderSubmission>
 */
class OrderSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->inProgress(),
            'user_id' => User::factory(['role' => User::ROLE_PERFORMER]),
            'message' => fake()->paragraph(),
            'status' => OrderSubmission::STATUS_SUBMITTED,
        ];
    }
}
