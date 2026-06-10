<?php

namespace Database\Factories;

use App\Models\PaymentOperation;
use App\Models\ProviderWebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderWebhookEvent>
 */
class ProviderWebhookEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider' => PaymentOperation::PROVIDER_STUB,
            'event_id' => fake()->uuid(),
            'event_type' => fake()->randomElement(ProviderWebhookEvent::futureEventTypes()),
            'status' => ProviderWebhookEvent::STATUS_RECEIVED,
            'payload' => ['factory' => true],
            'processed_at' => null,
            'error_message' => null,
        ];
    }
}
