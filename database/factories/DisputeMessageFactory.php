<?php

namespace Database\Factories;

use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisputeMessage>
 */
class DisputeMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'dispute_id' => Dispute::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'is_system' => false,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (): array => [
            'body' => 'Системное сообщение спора.',
            'is_system' => true,
        ]);
    }
}
