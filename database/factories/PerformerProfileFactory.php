<?php

namespace Database\Factories;

use App\Models\PerformerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformerProfile>
 */
class PerformerProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(['role' => User::ROLE_PERFORMER]),
            'display_name' => fake()->name(),
            'headline' => fake()->sentence(6),
            'bio' => fake()->paragraphs(3, true),
            'experience_years' => fake()->numberBetween(1, 12),
            'response_time_label' => 'Отвечает в течение дня',
            'portfolio_summary' => fake()->paragraph(),
            'verification_status' => PerformerProfile::STATUS_NOT_SUBMITTED,
            'verification_note' => null,
            'verified_at' => null,
            'verified_by' => null,
            'submitted_for_verification_at' => null,
            'published_at' => now(),
            'is_public' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'verification_status' => PerformerProfile::STATUS_PENDING_REVIEW,
            'submitted_for_verification_at' => now(),
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (): array => [
            'verification_status' => PerformerProfile::STATUS_VERIFIED,
            'verified_at' => now(),
            'verification_note' => null,
        ]);
    }
}
