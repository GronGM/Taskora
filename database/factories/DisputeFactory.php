<?php

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dispute>
 */
class DisputeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->inProgress(),
            'opened_by' => User::factory(['role' => User::ROLE_CUSTOMER]),
            'resolved_by' => null,
            'status' => Dispute::STATUS_OPEN,
            'reason' => fake()->randomElement(Dispute::reasons()),
            'description' => fake()->paragraph(),
            'previous_order_status' => Order::STATUS_IN_PROGRESS,
            'previous_payment_status' => Order::PAYMENT_HELD,
            'resolution' => null,
            'moderator_comment' => null,
            'resolved_at' => null,
            'canceled_at' => null,
        ];
    }

    public function underReview(): static
    {
        return $this->state(fn (): array => [
            'status' => Dispute::STATUS_UNDER_REVIEW,
        ]);
    }

    public function resolved(string $resolution = Dispute::RESOLUTION_RELEASE_TO_PERFORMER): static
    {
        return $this->state(fn (): array => [
            'status' => Dispute::STATUS_RESOLVED,
            'resolved_by' => User::factory(['role' => User::ROLE_MODERATOR]),
            'resolution' => $resolution,
            'moderator_comment' => 'Решение принято после проверки материалов заказа.',
            'resolved_at' => now(),
        ]);
    }
}
