<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->numberBetween(2000, 40000);
        $feePercent = 15.00;
        $feeAmount = (int) round($price * $feePercent / 100);

        return [
            'customer_id' => User::factory(['role' => User::ROLE_CUSTOMER]),
            'performer_id' => User::factory(['role' => User::ROLE_PERFORMER]),
            'category_id' => Category::factory(),
            'source_type' => Order::SOURCE_SERVICE,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'price' => $price,
            'delivery_days' => fake()->numberBetween(2, 14),
            'platform_fee_percent' => $feePercent,
            'platform_fee_amount' => $feeAmount,
            'performer_amount' => $price - $feeAmount,
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'payment_status' => Order::PAYMENT_UNPAID,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
            'started_at' => now(),
        ]);
    }

    public function submittedForReview(): static
    {
        return $this->state(fn (): array => [
            'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_HELD,
            'started_at' => now()->subDay(),
            'submitted_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::PAYMENT_RELEASED,
            'started_at' => now()->subDays(3),
            'submitted_at' => now()->subDay(),
            'completed_at' => now(),
        ]);
    }
}
