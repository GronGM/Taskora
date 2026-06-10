<?php

namespace Database\Factories;

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\PaymentOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_operation_id' => PaymentOperation::factory(),
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'account' => LedgerEntry::ACCOUNT_ESCROW,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount' => fake()->numberBetween(1000, 50000),
            'currency' => 'RUB',
            'description' => fake()->sentence(),
            'reference_type' => null,
            'reference_id' => null,
            'posted_at' => now(),
        ];
    }
}
