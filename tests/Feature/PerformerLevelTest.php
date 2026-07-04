<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Services\Reviews\ReviewAggregateService;
use App\Support\PerformerLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformerLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_thresholds_are_applied(): void
    {
        $this->assertSame(PerformerLevel::NOVICE, PerformerLevel::determine(0, null, 0));
        $this->assertSame(PerformerLevel::NOVICE, PerformerLevel::determine(2, 5.0, 0));
        $this->assertSame(PerformerLevel::SPECIALIST, PerformerLevel::determine(3, null, 0));
        $this->assertSame(PerformerLevel::SPECIALIST, PerformerLevel::determine(9, 5.0, 0));
        $this->assertSame(PerformerLevel::PRO, PerformerLevel::determine(10, 4.5, 1));
        $this->assertSame(PerformerLevel::SPECIALIST, PerformerLevel::determine(10, 4.4, 0));
        $this->assertSame(PerformerLevel::EXPERT, PerformerLevel::determine(30, 4.7, 1));
        $this->assertSame(PerformerLevel::PRO, PerformerLevel::determine(30, 4.6, 0));
    }

    public function test_lost_disputes_share_blocks_level_up(): void
    {
        // 3 завершенных заказа и 1 проигранный спор: доля 33% выше порога 20%.
        $this->assertSame(PerformerLevel::NOVICE, PerformerLevel::determine(3, 5.0, 1));
        // 10 завершенных и 1 проигранный: 10% — допустимо для Профи.
        $this->assertSame(PerformerLevel::PRO, PerformerLevel::determine(10, 4.6, 1));
    }

    public function test_recalculate_updates_level_and_lost_disputes(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        foreach (range(1, 3) as $i) {
            Order::factory()
                ->for($customer, 'customer')
                ->for($performer, 'performer')
                ->completed()
                ->create();
        }

        app(ReviewAggregateService::class)->recalculatePerformer($performer);

        $performer->refresh();
        $this->assertSame(3, $performer->performer_completed_orders_count);
        $this->assertSame(0, $performer->performer_lost_disputes_count);
        $this->assertSame(PerformerLevel::SPECIALIST, $performer->performer_level);
    }

    public function test_lost_dispute_is_counted_and_downgrades_level(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        foreach (range(1, 3) as $i) {
            Order::factory()
                ->for($customer, 'customer')
                ->for($performer, 'performer')
                ->completed()
                ->create();
        }

        $disputedOrder = Order::factory()
            ->for($customer, 'customer')
            ->for($performer, 'performer')
            ->create(['status' => Order::STATUS_CANCELED, 'payment_status' => Order::PAYMENT_REFUNDED]);

        Dispute::factory()
            ->for($disputedOrder)
            ->for($customer, 'openedBy')
            ->create([
                'status' => Dispute::STATUS_RESOLVED,
                'resolution' => Dispute::RESOLUTION_REFUND_TO_CUSTOMER,
                'resolved_at' => now(),
            ]);

        app(ReviewAggregateService::class)->recalculatePerformer($performer);

        $performer->refresh();
        $this->assertSame(1, $performer->performer_lost_disputes_count);
        $this->assertSame(PerformerLevel::NOVICE, $performer->performer_level);
    }

    public function test_performers_page_exposes_level_label(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER, 'performer_level' => PerformerLevel::SPECIALIST]);
        Service::factory()->for($performer, 'user')->create(['status' => Service::STATUS_PUBLISHED]);

        $payload = collect($this->get('/performers')->assertOk()->inertiaProps('performers'))
            ->firstWhere('id', $performer->id);

        $this->assertSame('Специалист', $payload['level_label']);
        $this->assertSame(PerformerLevel::SPECIALIST, $payload['level']);
    }

    public function test_public_profile_shows_level_badge(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER, 'performer_level' => PerformerLevel::PRO]);
        Service::factory()->for($performer, 'user')->create(['status' => Service::STATUS_PUBLISHED]);

        $badges = collect($this->get(route('performers.show', $performer))->assertOk()->inertiaProps('performer')['trust_badges'])
            ->pluck('label');

        $this->assertTrue($badges->contains('Профи'));
    }
}
