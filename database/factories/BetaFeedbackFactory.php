<?php

namespace Database\Factories;

use App\Models\BetaFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BetaFeedback>
 */
class BetaFeedbackFactory extends Factory
{
    protected $model = BetaFeedback::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'role' => User::ROLE_CUSTOMER,
            'page_url' => '/catalog',
            'scenario' => 'Проверка каталога',
            'type' => BetaFeedback::TYPE_BUG,
            'severity' => BetaFeedback::SEVERITY_MEDIUM,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'browser' => 'Test browser',
            'screen_size' => '1280x720',
            'status' => BetaFeedback::STATUS_OPEN,
        ];
    }
}
