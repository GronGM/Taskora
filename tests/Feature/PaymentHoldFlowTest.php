<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PaymentHoldFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_performer_can_cancel_only_awaiting_unpaid_order(): void
    {
        [$performer, $order] = $this->orderForPerformer([
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'payment_status' => Order::PAYMENT_UNPAID,
        ]);

        $this->actingAs($performer)
            ->post(route('performer.orders.cancel', $order))
            ->assertRedirect(route('performer.orders.show', $order));

        $this->assertSame(Order::STATUS_CANCELED, $order->refresh()->status);
        $this->assertSame(Order::PAYMENT_CANCELED, $order->payment_status);

        foreach ([Order::PAYMENT_HELD, Order::PAYMENT_RELEASED] as $paymentStatus) {
            [$performer, $blockedOrder] = $this->orderForPerformer([
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'payment_status' => $paymentStatus,
            ]);

            $this->actingAs($performer)
                ->post(route('performer.orders.cancel', $blockedOrder))
                ->assertForbidden();
        }

        foreach ([Order::STATUS_DISPUTED, Order::STATUS_CANCELED] as $status) {
            [$performer, $blockedOrder] = $this->orderForPerformer([
                'status' => $status,
                'payment_status' => Order::PAYMENT_UNPAID,
            ]);

            $this->actingAs($performer)
                ->post(route('performer.orders.cancel', $blockedOrder))
                ->assertForbidden();
        }
    }

    public function test_performer_cannot_cancel_in_progress_order(): void
    {
        [$performer, $order] = $this->orderForPerformer([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $this->actingAs($performer)
            ->post(route('performer.orders.cancel', $order))
            ->assertForbidden();

        $this->assertSame(Order::STATUS_IN_PROGRESS, $order->refresh()->status);
    }

    public function test_performer_cannot_cancel_submitted_for_review_order(): void
    {
        [, $performer, $order] = $this->submittedOrder();

        $this->actingAs($performer)
            ->post(route('performer.orders.cancel', $order))
            ->assertForbidden();

        $this->assertSame(Order::STATUS_SUBMITTED_FOR_REVIEW, $order->refresh()->status);
    }

    public function test_performer_cannot_cancel_revision_requested_order(): void
    {
        [$performer, $order] = $this->orderForPerformer([
            'status' => Order::STATUS_REVISION_REQUESTED,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $this->actingAs($performer)
            ->post(route('performer.orders.cancel', $order))
            ->assertForbidden();

        $this->assertSame(Order::STATUS_REVISION_REQUESTED, $order->refresh()->status);
    }

    public function test_performer_cannot_cancel_completed_order(): void
    {
        [$performer, $order] = $this->orderForPerformer([
            'status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::PAYMENT_RELEASED,
        ]);

        $this->actingAs($performer)
            ->post(route('performer.orders.cancel', $order))
            ->assertForbidden();

        $this->assertSame(Order::STATUS_COMPLETED, $order->refresh()->status);
    }

    public function test_submit_work_starts_review_hold(): void
    {
        [$performer, $order] = $this->orderForPerformer([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
            'review_hold_days' => 10,
        ]);

        $this->submitWork($performer, $order);

        $this->assertSame(Order::STATUS_SUBMITTED_FOR_REVIEW, $order->refresh()->status);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $performer->id,
            'type' => OrderEvent::TYPE_REVIEW_HOLD_STARTED,
        ]);
    }

    public function test_submit_work_fills_review_hold_started_at(): void
    {
        $now = Carbon::parse('2026-06-10 12:00:00');
        $this->travelTo($now);
        [$performer, $order] = $this->orderForPerformer([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $this->submitWork($performer, $order);

        $this->assertSame($now->toDateTimeString(), $order->refresh()->review_hold_started_at->toDateTimeString());
    }

    public function test_submit_work_fills_review_hold_until(): void
    {
        $now = Carbon::parse('2026-06-10 12:00:00');
        $this->travelTo($now);
        [$performer, $order] = $this->orderForPerformer([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
            'review_hold_days' => 12,
        ]);

        $this->submitWork($performer, $order);

        $this->assertSame($now->copy()->addDays(12)->toDateTimeString(), $order->refresh()->review_hold_until->toDateTimeString());
        $this->assertSame($order->review_hold_until->toDateTimeString(), $order->auto_release_at->toDateTimeString());
    }

    public function test_submit_work_keeps_payment_held(): void
    {
        [$performer, $order] = $this->orderForPerformer([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $this->submitWork($performer, $order);

        $this->assertSame(Order::PAYMENT_HELD, $order->refresh()->payment_status);
    }

    public function test_customer_can_early_accept_submitted_work(): void
    {
        [$customer, , $order] = $this->submittedOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.complete', $order))
            ->assertRedirect(route('customer.orders.show', $order));

        $this->assertSame(Order::STATUS_COMPLETED, $order->refresh()->status);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_ORDER_COMPLETED,
        ]);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_FUNDS_RELEASED,
        ]);
    }

    public function test_customer_early_accept_releases_payment(): void
    {
        [$customer, , $order] = $this->submittedOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.complete', $order));

        $this->assertSame(Order::PAYMENT_RELEASED, $order->refresh()->payment_status);
    }

    public function test_customer_early_accept_fills_released_at(): void
    {
        [$customer, , $order] = $this->submittedOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.complete', $order));

        $this->assertNotNull($order->refresh()->released_at);
    }

    public function test_customer_early_accept_sets_release_reason(): void
    {
        [$customer, , $order] = $this->submittedOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.complete', $order));

        $this->assertSame(Order::RELEASE_CUSTOMER_EARLY_ACCEPT, $order->refresh()->release_reason);
    }

    public function test_customer_request_revision_sets_revision_requested_status(): void
    {
        [$customer, , $order] = $this->submittedOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.request-revision', $order), $this->revisionPayload());

        $this->assertSame(Order::STATUS_REVISION_REQUESTED, $order->refresh()->status);
    }

    public function test_customer_request_revision_does_not_release_funds(): void
    {
        [$customer, , $order] = $this->submittedOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.request-revision', $order), $this->revisionPayload());

        $order->refresh();

        $this->assertSame(Order::PAYMENT_HELD, $order->payment_status);
        $this->assertNull($order->released_at);
        $this->assertNull($order->review_hold_until);
        $this->assertNull($order->auto_release_at);
    }

    public function test_resubmission_after_revision_restarts_review_hold(): void
    {
        [$customer, $performer, $order] = $this->submittedOrder([
            'review_hold_started_at' => Carbon::parse('2026-06-01 10:00:00'),
            'review_hold_until' => Carbon::parse('2026-06-11 10:00:00'),
            'auto_release_at' => Carbon::parse('2026-06-11 10:00:00'),
            'review_hold_days' => 10,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.request-revision', $order), $this->revisionPayload());

        $newSubmittedAt = Carbon::parse('2026-06-12 15:30:00');
        $this->travelTo($newSubmittedAt);

        $this->submitWork($performer, $order->refresh());

        $this->assertSame($newSubmittedAt->toDateTimeString(), $order->refresh()->review_hold_started_at->toDateTimeString());
        $this->assertSame($newSubmittedAt->copy()->addDays(10)->toDateTimeString(), $order->review_hold_until->toDateTimeString());
    }

    public function test_release_due_command_auto_completes_due_orders(): void
    {
        [, , $order] = $this->submittedOrder([
            'review_hold_until' => now()->subMinute(),
            'auto_release_at' => now()->subMinute(),
        ]);

        $this->artisan('orders:release-due')
            ->expectsOutput('Разблокировано заказов: 1')
            ->assertExitCode(0);

        $this->assertSame(Order::STATUS_COMPLETED, $order->refresh()->status);
    }

    public function test_release_due_command_does_not_complete_not_due_orders(): void
    {
        [, , $order] = $this->submittedOrder([
            'review_hold_until' => now()->addDay(),
            'auto_release_at' => now()->addDay(),
        ]);

        $this->artisan('orders:release-due')
            ->expectsOutput('Разблокировано заказов: 0')
            ->assertExitCode(0);

        $this->assertSame(Order::STATUS_SUBMITTED_FOR_REVIEW, $order->refresh()->status);
    }

    public function test_release_due_command_does_not_touch_disputed_canceled_or_completed_orders(): void
    {
        $dueAt = now()->subMinute();
        $disputed = Order::factory()->create([
            'status' => Order::STATUS_DISPUTED,
            'payment_status' => Order::PAYMENT_HELD,
            'review_hold_until' => $dueAt,
        ]);
        $canceled = Order::factory()->create([
            'status' => Order::STATUS_CANCELED,
            'payment_status' => Order::PAYMENT_CANCELED,
            'review_hold_until' => $dueAt,
        ]);
        $completed = Order::factory()->completed()->create([
            'review_hold_until' => $dueAt,
        ]);

        $this->artisan('orders:release-due')
            ->expectsOutput('Разблокировано заказов: 0')
            ->assertExitCode(0);

        $this->assertSame(Order::STATUS_DISPUTED, $disputed->refresh()->status);
        $this->assertSame(Order::STATUS_CANCELED, $canceled->refresh()->status);
        $this->assertSame(Order::STATUS_COMPLETED, $completed->refresh()->status);
    }

    public function test_auto_release_sets_payment_released(): void
    {
        [, , $order] = $this->submittedOrder([
            'review_hold_until' => now()->subMinute(),
            'auto_release_at' => now()->subMinute(),
        ]);

        $this->artisan('orders:release-due');

        $this->assertSame(Order::PAYMENT_RELEASED, $order->refresh()->payment_status);
    }

    public function test_auto_release_sets_release_reason(): void
    {
        [, , $order] = $this->submittedOrder([
            'review_hold_until' => now()->subMinute(),
            'auto_release_at' => now()->subMinute(),
        ]);

        $this->artisan('orders:release-due');

        $this->assertSame(Order::RELEASE_AUTO, $order->refresh()->release_reason);
    }

    public function test_auto_release_creates_funds_released_event(): void
    {
        [, , $order] = $this->submittedOrder([
            'review_hold_until' => now()->subMinute(),
            'auto_release_at' => now()->subMinute(),
        ]);

        $this->artisan('orders:release-due');

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => null,
            'type' => OrderEvent::TYPE_FUNDS_RELEASED,
        ]);
    }

    public function test_customer_sees_review_date_in_workspace(): void
    {
        $reviewHoldUntil = Carbon::parse('2026-06-20 16:00:00');
        [$customer, , $order] = $this->submittedOrder([
            'review_hold_until' => $reviewHoldUntil,
            'auto_release_at' => $reviewHoldUntil,
        ]);

        $response = $this->actingAs($customer)
            ->get(route('customer.orders.workspace', $order))
            ->assertOk();

        $this->assertSame($reviewHoldUntil->format('d.m.Y H:i'), $response->inertiaProps('order.review_hold_until'));
    }

    public function test_performer_sees_review_date_in_workspace(): void
    {
        $reviewHoldUntil = Carbon::parse('2026-06-20 16:00:00');
        [, $performer, $order] = $this->submittedOrder([
            'review_hold_until' => $reviewHoldUntil,
            'auto_release_at' => $reviewHoldUntil,
        ]);

        $response = $this->actingAs($performer)
            ->get(route('performer.orders.workspace', $order))
            ->assertOk();

        $this->assertSame($reviewHoldUntil->format('d.m.Y H:i'), $response->inertiaProps('order.review_hold_until'));
    }

    public function test_performer_cancel_button_is_hidden_after_payment(): void
    {
        [$performer, $order] = $this->orderForPerformer([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $response = $this->actingAs($performer)
            ->get(route('performer.orders.workspace', $order))
            ->assertOk();

        $this->assertFalse($response->inertiaProps('order.can.cancel_as_performer'));
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{0: User, 1: Order}
     */
    private function orderForPerformer(array $state = []): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()
            ->for($customer, 'customer')
            ->for($performer, 'performer')
            ->create($state);

        return [$performer, $order];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{0: User, 1: User, 2: Order}
     */
    private function submittedOrder(array $state = []): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $reviewHoldStartedAt = $state['review_hold_started_at'] ?? now()->subDay();
        $reviewHoldUntil = $state['review_hold_until'] ?? $reviewHoldStartedAt->copy()->addDays(Order::REVIEW_HOLD_DEFAULT_DAYS);

        $order = Order::factory()
            ->for($customer, 'customer')
            ->for($performer, 'performer')
            ->create(array_merge([
                'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
                'payment_status' => Order::PAYMENT_HELD,
                'started_at' => now()->subDays(2),
                'submitted_at' => $reviewHoldStartedAt,
                'review_hold_days' => Order::REVIEW_HOLD_DEFAULT_DAYS,
                'review_hold_started_at' => $reviewHoldStartedAt,
                'review_hold_until' => $reviewHoldUntil,
                'auto_release_at' => $state['auto_release_at'] ?? $reviewHoldUntil,
            ], $state));

        OrderSubmission::factory()->for($order)->for($performer, 'user')->create([
            'status' => OrderSubmission::STATUS_SUBMITTED,
        ]);

        return [$customer, $performer, $order];
    }

    private function submitWork(User $performer, Order $order): void
    {
        $this->actingAs($performer)
            ->post(route('performer.orders.submit-work', $order), [
                'message' => 'Работа готова, проверьте результат.',
            ])
            ->assertRedirect(route('performer.orders.show', $order));
    }

    /**
     * @return array<string, string>
     */
    private function revisionPayload(): array
    {
        return [
            'revision_comment' => 'Please correct the submitted result and upload the revised files.',
        ];
    }
}
