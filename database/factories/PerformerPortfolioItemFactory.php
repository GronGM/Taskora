<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\PerformerPortfolioItem;
use App\Models\PerformerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerformerPortfolioItem>
 */
class PerformerPortfolioItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'performer_profile_id' => PerformerProfile::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'category_id' => Category::factory(),
            'image_path' => null,
            'file_path' => null,
            'external_url' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_public' => true,
            'status' => PerformerPortfolioItem::STATUS_PUBLISHED,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => PerformerPortfolioItem::STATUS_DRAFT,
            'is_public' => false,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => [
            'status' => PerformerPortfolioItem::STATUS_HIDDEN,
            'is_public' => false,
        ]);
    }
}
