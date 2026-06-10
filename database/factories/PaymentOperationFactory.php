<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\PaymentOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentOperation>
 */
class PaymentOperationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'provider' => PaymentOperation::PROVIDER_STUB,
            'provider_operation_id' => null,
            'type' => PaymentOperation::TYPE_PAYMENT_HOLD,
            'status' => PaymentOperation::STATUS_SUCCEEDED,
            'amount' => fake()->numberBetween(1000, 50000),
            'currency' => 'RUB',
            'idempotency_key' => fake()->unique()->uuid(),
            'description' => fake()->sentence(),
            'payload' => ['factory' => true],
            'succeeded_at' => now(),
            'failed_at' => null,
            'canceled_at' => null,
        ];
    }
}
