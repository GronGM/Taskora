<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_shows_live_stats(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        Order::factory()->for($customer, 'customer')->for($performer, 'performer')->inProgress()->create();
        Order::factory()->for($customer, 'customer')->for($performer, 'performer')->submittedForReview()->create();
        Order::factory()->for($customer, 'customer')->for($performer, 'performer')->create();

        $task = Task::factory()->for($customer, 'customer')->create(['status' => Task::STATUS_PUBLISHED, 'offers_count' => 1]);
        TaskOffer::factory()->for($task)->for($performer, 'performer')->create(['status' => TaskOffer::STATUS_SUBMITTED]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.orders_in_progress', 1)
                ->where('stats.orders_to_review', 1)
                ->where('stats.orders_awaiting_payment', 1)
                ->where('stats.pending_offers', 1)
                ->has('attention.needs_review', 1)
                ->has('attention.tasks_with_offers', 1));
    }

    public function test_customer_dashboard_ignores_foreign_orders(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $foreign = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        Order::factory()->for($foreign, 'customer')->for($performer, 'performer')->inProgress()->create();

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('stats.orders_in_progress', 0));
    }

    public function test_performer_dashboard_shows_live_stats_and_finance(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        Order::factory()->for($customer, 'customer')->for($performer, 'performer')->inProgress()->create();
        Order::factory()->for($customer, 'customer')->for($performer, 'performer')->create([
            'status' => Order::STATUS_REVISION_REQUESTED,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $task = Task::factory()->create(['status' => Task::STATUS_PUBLISHED]);
        TaskOffer::factory()->for($task)->for($performer, 'performer')->create(['status' => TaskOffer::STATUS_SUBMITTED]);

        $this->actingAs($performer)
            ->get(route('performer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.orders_in_progress', 1)
                ->where('stats.orders_revision', 1)
                ->where('stats.active_offers', 1)
                ->where('stats.available_amount', 0)
                ->has('attention.needs_revision', 1));
    }

    public function test_performer_available_amount_grows_after_release(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        $order = Order::factory()->for($customer, 'customer')->for($performer, 'performer')->create([
            'price' => 10000,
            'platform_fee_amount' => 1500,
            'performer_amount' => 8500,
        ]);

        $this->actingAs($customer)->post(route('customer.orders.mark-paid', $order));

        $this->actingAs($performer)
            ->post(route('performer.orders.submit-work', $order->refresh()), ['message' => 'Работа готова, проверяйте.']);

        $this->actingAs($customer)->post(route('customer.orders.complete', $order->refresh()));

        $this->actingAs($performer)
            ->get(route('performer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.available_amount', 8500)
                ->where('stats.orders_completed', 1));
    }
}
