<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderSubmission;
use App\Models\PaymentOperation;
use App\Models\PayoutRequest;
use App\Models\ProviderWebhookEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentLedgerArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_paid_creates_payment_hold_operation(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->markPaid($customer, $order);

        $this->assertDatabaseHas('payment_operations', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => PaymentOperation::TYPE_PAYMENT_HOLD,
            'status' => PaymentOperation::STATUS_SUCCEEDED,
            'amount' => $order->price,
            'provider' => PaymentOperation::PROVIDER_STUB,
        ]);
    }

    public function test_mark_paid_creates_ledger_entries(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->markPaid($customer, $order);

        $this->assertSame(4, LedgerEntry::query()->where('order_id', $order->id)->count());
    }

    public function test_repeated_mark_paid_does_not_duplicate_payment_operation(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->markPaid($customer, $order);

        $this->actingAs($customer)
            ->post(route('customer.orders.mark-paid', $order))
            ->assertForbidden();

        $this->assertSame(1, PaymentOperation::query()->where('order_id', $order->id)->where('type', PaymentOperation::TYPE_PAYMENT_HOLD)->count());
    }

    public function test_payment_hold_ledger_contains_escrow(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->markPaid($customer, $order);

        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'account' => LedgerEntry::ACCOUNT_ESCROW,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount' => $order->price,
        ]);
    }

    public function test_payment_hold_ledger_contains_performer_pending(): void
    {
        [$customer, $performer, $order] = $this->orderScenario();

        $this->markPaid($customer, $order);

        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'user_id' => $performer->id,
            'account' => LedgerEntry::ACCOUNT_PERFORMER_PENDING,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount' => $order->performer_amount,
        ]);
    }

    public function test_payment_hold_ledger_contains_platform_fee(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->markPaid($customer, $order);

        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'account' => LedgerEntry::ACCOUNT_PLATFORM_FEE,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount' => $order->platform_fee_amount,
        ]);
    }

    public function test_complete_creates_release_to_performer_operation(): void
    {
        [$customer, , $order] = $this->submittedPaidOrder();

        $this->actingAs($customer)
            ->post(route('customer.orders.complete', $order))
            ->assertRedirect(route('customer.orders.show', $order));

        $this->assertDatabaseHas('payment_operations', [
            'order_id' => $order->id,
            'type' => PaymentOperation::TYPE_RELEASE_TO_PERFORMER,
            'status' => PaymentOperation::STATUS_SUCCEEDED,
            'amount' => $order->performer_amount,
        ]);
    }

    public function test_complete_moves_performer_pending_to_available_through_ledger(): void
    {
        [$customer, $performer, $order] = $this->submittedPaidOrder();

        $this->actingAs($customer)->post(route('customer.orders.complete', $order));

        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'user_id' => $performer->id,
            'account' => LedgerEntry::ACCOUNT_PERFORMER_PENDING,
            'direction' => LedgerEntry::DIRECTION_DEBIT,
            'amount' => $order->performer_amount,
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'user_id' => $performer->id,
            'account' => LedgerEntry::ACCOUNT_PERFORMER_AVAILABLE,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount' => $order->performer_amount,
        ]);
    }

    public function test_auto_release_creates_release_to_performer_operation(): void
    {
        [, , $order] = $this->submittedPaidOrder([
            'review_hold_until' => now()->subMinute(),
            'auto_release_at' => now()->subMinute(),
        ]);

        $this->artisan('orders:release-due')
            ->expectsOutput('Разблокировано заказов: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('payment_operations', [
            'order_id' => $order->id,
            'type' => PaymentOperation::TYPE_RELEASE_TO_PERFORMER,
        ]);
    }

    public function test_dispute_release_to_performer_creates_release_operation(): void
    {
        [$moderator, , , $order, $dispute] = $this->disputedPaidOrder();

        $this->resolveDispute($moderator, $dispute, Dispute::RESOLUTION_RELEASE_TO_PERFORMER);

        $this->assertDatabaseHas('payment_operations', [
            'order_id' => $order->id,
            'type' => PaymentOperation::TYPE_RELEASE_TO_PERFORMER,
        ]);
    }

    public function test_dispute_refund_to_customer_creates_refund_operation(): void
    {
        [$moderator, , , $order, $dispute] = $this->disputedPaidOrder();

        $this->resolveDispute($moderator, $dispute, Dispute::RESOLUTION_REFUND_TO_CUSTOMER);

        $this->assertDatabaseHas('payment_operations', [
            'order_id' => $order->id,
            'type' => PaymentOperation::TYPE_REFUND_TO_CUSTOMER,
            'amount' => $order->price,
        ]);
    }

    public function test_refund_to_customer_creates_customer_refund_ledger_entry(): void
    {
        [$moderator, , , $order, $dispute] = $this->disputedPaidOrder();

        $this->resolveDispute($moderator, $dispute, Dispute::RESOLUTION_REFUND_TO_CUSTOMER);

        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'account' => LedgerEntry::ACCOUNT_CUSTOMER_REFUND,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount' => $order->price,
        ]);
    }

    public function test_refund_to_customer_creates_platform_fee_reverse_when_fee_was_reserved(): void
    {
        [$moderator, , , $order, $dispute] = $this->disputedPaidOrder();

        $this->resolveDispute($moderator, $dispute, Dispute::RESOLUTION_REFUND_TO_CUSTOMER);

        $this->assertDatabaseHas('payment_operations', [
            'order_id' => $order->id,
            'type' => PaymentOperation::TYPE_PLATFORM_FEE_REVERSE,
            'amount' => $order->platform_fee_amount,
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'account' => LedgerEntry::ACCOUNT_PLATFORM_FEE,
            'direction' => LedgerEntry::DIRECTION_DEBIT,
            'amount' => $order->platform_fee_amount,
        ]);
    }

    public function test_cancel_unpaid_order_creates_no_payment_operation(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.cancel', $order))
            ->assertRedirect(route('customer.orders.show', $order));

        $this->assertDatabaseMissing('payment_operations', [
            'order_id' => $order->id,
        ]);
    }

    public function test_performer_sees_performer_finance(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        $response = $this->actingAs($performer)
            ->get(route('performer.finance.index'))
            ->assertOk();

        $this->assertSame('Performer/Finance/Index', $response->inertiaPage()['component']);
    }

    public function test_customer_cannot_see_performer_finance(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
            ->get(route('performer.finance.index'))
            ->assertForbidden();
    }

    public function test_performer_finance_shows_pending_and_available_summary(): void
    {
        [$pendingCustomer, $performer, $pendingOrder] = $this->orderScenario();
        $this->markPaid($pendingCustomer, $pendingOrder);

        [$releasedCustomer, , $releasedOrder] = $this->submittedPaidOrder(performer: $performer);
        $this->actingAs($releasedCustomer)->post(route('customer.orders.complete', $releasedOrder));

        $response = $this->actingAs($performer)
            ->get(route('performer.finance.index'))
            ->assertOk();

        $this->assertSame($pendingOrder->performer_amount, $response->inertiaProps('summary.pending_amount'));
        $this->assertSame($releasedOrder->performer_amount, $response->inertiaProps('summary.available_amount'));
    }

    public function test_admin_sees_admin_finance(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->get(route('admin.finance.index'))
            ->assertOk();

        $this->assertSame('Admin/Finance/Index', $response->inertiaPage()['component']);
    }

    public function test_admin_finance_page_uses_russian_visible_labels(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.finance.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Finance/Index'));

        $source = file_get_contents(resource_path('js/Pages/Admin/Finance/Index.jsx'));

        $this->assertStringContainsString('Финансовая сводка', $source);
        $this->assertStringContainsString('Провайдер', $source);
        $this->assertStringContainsString('Платежные операции', $source);
        $this->assertStringNotContainsString('Admin finance', $source);
        $this->assertStringNotContainsString('Provider', $source);
    }

    public function test_admin_sees_payment_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->get(route('admin.payment-settings.index'))
            ->assertOk();

        $this->assertSame('Admin/PaymentSettings/Index', $response->inertiaPage()['component']);
        $this->assertSame('stub', $response->inertiaProps('settings.taskora_payments_mode'));
        $this->assertStringContainsString('stub-режим', $response->inertiaProps('warning'));
    }

    public function test_non_admin_users_cannot_see_payment_settings(): void
    {
        foreach ([User::ROLE_MODERATOR, User::ROLE_CUSTOMER, User::ROLE_PERFORMER] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.payment-settings.index'))
                ->assertForbidden();
        }
    }

    public function test_guest_is_redirected_from_payment_settings(): void
    {
        $this->get(route('admin.payment-settings.index'))
            ->assertRedirect('/login');
    }

    public function test_payment_settings_does_not_expose_secret_key_value(): void
    {
        config([
            'payments.yookassa.secret_key' => 'super-secret-test-key',
            'payments.yookassa.shop_id' => 'demo-shop',
        ]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->get(route('admin.payment-settings.index'))
            ->assertOk();

        $this->assertTrue($response->inertiaProps('settings.yookassa.secret_key_present'));
        $this->assertTrue($response->inertiaProps('settings.yookassa.shop_id_present'));
        $this->assertStringNotContainsString('super-secret-test-key', $response->getContent());
    }

    public function test_moderator_customer_and_performer_cannot_see_admin_finance(): void
    {
        foreach ([User::ROLE_MODERATOR, User::ROLE_CUSTOMER, User::ROLE_PERFORMER] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.finance.index'))
                ->assertForbidden();
        }
    }

    public function test_provider_webhook_events_table_exists_and_factory_works(): void
    {
        $event = ProviderWebhookEvent::factory()->create();

        $this->assertDatabaseHas('provider_webhook_events', [
            'id' => $event->id,
            'provider' => PaymentOperation::PROVIDER_STUB,
            'status' => ProviderWebhookEvent::STATUS_RECEIVED,
        ]);
    }

    public function test_payout_requests_table_exists_and_factory_works(): void
    {
        $request = PayoutRequest::factory()->pendingReview()->create();

        $this->assertDatabaseHas('payout_requests', [
            'id' => $request->id,
            'status' => PayoutRequest::STATUS_PENDING_REVIEW,
        ]);
    }

    public function test_ledger_entries_are_added_not_edited_in_release_flow(): void
    {
        [$customer, , $order] = $this->submittedPaidOrder();
        $before = LedgerEntry::query()
            ->where('order_id', $order->id)
            ->get()
            ->mapWithKeys(fn (LedgerEntry $entry): array => [$entry->id => $entry->updated_at->toDateTimeString()]);

        $this->actingAs($customer)->post(route('customer.orders.complete', $order));

        $this->assertGreaterThan($before->count(), LedgerEntry::query()->where('order_id', $order->id)->count());

        foreach ($before as $id => $updatedAt) {
            $this->assertSame($updatedAt, LedgerEntry::query()->findOrFail($id)->updated_at->toDateTimeString());
        }
    }

    public function test_platform_fee_amount_equals_order_platform_fee_amount(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->markPaid($customer, $order);

        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'account' => LedgerEntry::ACCOUNT_PLATFORM_FEE,
            'amount' => $order->platform_fee_amount,
        ]);
    }

    public function test_performer_amount_equals_order_performer_amount(): void
    {
        [$customer, $performer, $order] = $this->orderScenario();

        $this->markPaid($customer, $order);

        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id,
            'user_id' => $performer->id,
            'account' => LedgerEntry::ACCOUNT_PERFORMER_PENDING,
            'amount' => $order->performer_amount,
        ]);
    }

    public function test_after_refunded_order_available_performer_amount_does_not_increase(): void
    {
        [$moderator, , $performer, , $dispute] = $this->disputedPaidOrder();

        $this->resolveDispute($moderator, $dispute, Dispute::RESOLUTION_REFUND_TO_CUSTOMER);

        $response = $this->actingAs($performer)
            ->get(route('performer.finance.index'))
            ->assertOk();

        $this->assertSame(0, $response->inertiaProps('summary.available_amount'));
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{0: User, 1: User, 2: Order}
     */
    private function orderScenario(array $state = [], ?User $performer = null): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer ??= User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()
            ->for($customer, 'customer')
            ->for($performer, 'performer')
            ->create($state);

        return [$customer, $performer, $order];
    }

    private function markPaid(User $customer, Order $order): void
    {
        $this->actingAs($customer)
            ->post(route('customer.orders.mark-paid', $order))
            ->assertRedirect(route('customer.orders.show', $order));

        $order->refresh();
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{0: User, 1: User, 2: Order}
     */
    private function submittedPaidOrder(array $state = [], ?User $performer = null): array
    {
        [$customer, $performer, $order] = $this->orderScenario(performer: $performer);
        $this->markPaid($customer, $order);

        $submittedAt = $state['review_hold_started_at'] ?? now()->subDay();
        $reviewHoldUntil = $state['review_hold_until'] ?? $submittedAt->copy()->addDays(Order::REVIEW_HOLD_DEFAULT_DAYS);

        $order->update([
            'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_HELD,
            'submitted_at' => $submittedAt,
            'review_hold_started_at' => $submittedAt,
            'review_hold_until' => $reviewHoldUntil,
            'auto_release_at' => $state['auto_release_at'] ?? $reviewHoldUntil,
            ...$state,
        ]);

        OrderSubmission::factory()->for($order)->for($performer, 'user')->create([
            'status' => OrderSubmission::STATUS_SUBMITTED,
        ]);

        return [$customer, $performer, $order->refresh()];
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3: Order, 4: Dispute}
     */
    private function disputedPaidOrder(): array
    {
        [$customer, $performer, $order] = $this->orderScenario();
        $this->markPaid($customer, $order);

        $order->update([
            'status' => Order::STATUS_DISPUTED,
            'payment_status' => Order::PAYMENT_HELD,
            'review_hold_until' => null,
            'auto_release_at' => null,
        ]);

        $dispute = Dispute::factory()
            ->for($order)
            ->for($customer, 'openedBy')
            ->underReview()
            ->create([
                'previous_order_status' => Order::STATUS_IN_PROGRESS,
                'previous_payment_status' => Order::PAYMENT_HELD,
            ]);
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);

        return [$moderator, $customer, $performer, $order->refresh(), $dispute];
    }

    private function resolveDispute(User $moderator, Dispute $dispute, string $resolution): void
    {
        $this->actingAs($moderator)
            ->post(route('moderator.disputes.resolve', $dispute), [
                'resolution' => $resolution,
                'moderator_comment' => 'Проверил материалы заказа и принял решение.',
            ])
            ->assertRedirect(route('moderator.disputes.show', $dispute));
    }
}
