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
            'review_hold_days' => Order::REVIEW_HOLD_DEFAULT_DAYS,
            'review_hold_started_at' => null,
            'review_hold_until' => null,
            'auto_release_at' => null,
            'released_at' => null,
            'release_reason' => null,
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
        $submittedAt = now();
        $reviewHoldUntil = $submittedAt->copy()->addDays(Order::REVIEW_HOLD_DEFAULT_DAYS);

        return $this->state(fn (): array => [
            'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_HELD,
            'started_at' => now()->subDay(),
            'submitted_at' => $submittedAt,
            'review_hold_started_at' => $submittedAt,
            'review_hold_until' => $reviewHoldUntil,
            'auto_release_at' => $reviewHoldUntil,
        ]);
    }

    public function completed(): static
    {
        $completedAt = now();

        return $this->state(fn (): array => [
            'status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::PAYMENT_RELEASED,
            'started_at' => now()->subDays(3),
            'submitted_at' => now()->subDay(),
            'completed_at' => $completedAt,
            'released_at' => $completedAt,
            'release_reason' => Order::RELEASE_CUSTOMER_EARLY_ACCEPT,
        ]);
    }
}
