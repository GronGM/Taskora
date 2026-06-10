<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        $order = Order::factory()->completed()->create();

        return [
            'order_id' => $order->id,
            'service_id' => $order->service_id,
            'task_id' => $order->task_id,
            'customer_id' => $order->customer_id,
            'performer_id' => $order->performer_id,
            'rating' => fake()->numberBetween(4, 5),
            'comment' => fake()->paragraph(),
            'status' => Review::STATUS_PUBLISHED,
            'is_public' => true,
            'published_at' => now(),
            'hidden_at' => null,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => [
            'status' => Review::STATUS_HIDDEN,
            'is_public' => false,
            'hidden_at' => now(),
        ]);
    }
}
