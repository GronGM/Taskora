<?php

namespace Database\Factories;

use App\Models\PayoutRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayoutRequest>
 */
class PayoutRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'performer_id' => User::factory(['role' => User::ROLE_PERFORMER]),
            'amount' => fake()->numberBetween(1000, 30000),
            'currency' => 'RUB',
            'status' => PayoutRequest::STATUS_DRAFT,
            'requested_at' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'paid_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function pendingReview(): static
    {
        return $this->state(fn (): array => [
            'status' => PayoutRequest::STATUS_PENDING_REVIEW,
            'requested_at' => now(),
        ]);
    }
}
