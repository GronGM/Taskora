<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\ModerationFlag;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\OrderSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisputeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_open_dispute_for_own_in_progress_order(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $response = $this->actingAs($customer)
            ->post(route('customer.orders.disputes.store', $order), $this->disputePayload());

        $dispute = Dispute::firstOrFail();

        $response->assertRedirect(route('customer.disputes.show', $dispute));
        $this->assertSame($customer->id, $dispute->opened_by);
    }

    public function test_performer_can_open_dispute_for_own_in_progress_order(): void
    {
        [, $performer, $order] = $this->orderScenario();

        $response = $this->actingAs($performer)
            ->post(route('performer.orders.disputes.store', $order), $this->disputePayload());

        $dispute = Dispute::firstOrFail();

        $response->assertRedirect(route('performer.disputes.show', $dispute));
        $this->assertSame($performer->id, $dispute->opened_by);
    }

    public function test_guest_cannot_open_dispute(): void
    {
        [, , $order] = $this->orderScenario();

        $this->post(route('customer.orders.disputes.store', $order), $this->disputePayload())
            ->assertRedirect('/login');
    }

    public function test_foreign_customer_cannot_open_dispute(): void
    {
        $foreignCustomer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        [, , $order] = $this->orderScenario();

        $this->actingAs($foreignCustomer)
            ->post(route('customer.orders.disputes.store', $order), $this->disputePayload())
            ->assertForbidden();
    }

    public function test_foreign_performer_cannot_open_dispute(): void
    {
        $foreignPerformer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        [, , $order] = $this->orderScenario();

        $this->actingAs($foreignPerformer)
            ->post(route('performer.orders.disputes.store', $order), $this->disputePayload())
            ->assertForbidden();
    }

    public function test_cannot_open_dispute_for_awaiting_payment_order(): void
    {
        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'payment_status' => Order::PAYMENT_UNPAID,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.disputes.store', $order), $this->disputePayload())
            ->assertForbidden();
    }

    public function test_cannot_open_dispute_for_completed_order(): void
    {
        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::PAYMENT_RELEASED,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.disputes.store', $order), $this->disputePayload())
            ->assertForbidden();
    }

    public function test_cannot_open_second_active_dispute_for_order(): void
    {
        [$customer, , $order] = $this->orderScenario();
        $this->createDispute($order, $customer);

        $this->actingAs($customer)
            ->post(route('customer.orders.disputes.store', $order), $this->disputePayload())
            ->assertForbidden();
    }

    public function test_opening_dispute_sets_order_status_to_disputed(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.disputes.store', $order), $this->disputePayload());

        $this->assertSame(Order::STATUS_DISPUTED, $order->refresh()->status);
    }

    public function test_opening_dispute_keeps_payment_held(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.disputes.store', $order), $this->disputePayload());

        $this->assertSame(Order::PAYMENT_HELD, $order->refresh()->payment_status);
    }

    public function test_opening_dispute_creates_order_event(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.disputes.store', $order), $this->disputePayload());

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_DISPUTE_OPENED,
        ]);
    }

    public function test_opening_dispute_creates_system_dispute_message(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.disputes.store', $order), $this->disputePayload());

        $this->assertDatabaseHas('dispute_messages', [
            'dispute_id' => Dispute::firstOrFail()->id,
            'is_system' => true,
        ]);
    }

    public function test_contact_guard_blocks_dispute_message_with_contact(): void
    {
        [$customer, , $order, $dispute] = $this->activeDisputeScenario();

        $this->actingAs($customer)
            ->post(route('customer.disputes.messages.store', $dispute), [
                'body' => 'Напиши мне на test@example.com',
            ])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseMissing('dispute_messages', [
            'dispute_id' => $dispute->id,
            'body' => 'Напиши мне на test@example.com',
        ]);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'type' => OrderEvent::TYPE_CONTACT_BLOCKED,
        ]);
    }

    public function test_blocked_dispute_message_creates_moderation_flag(): void
    {
        [$customer, , , $dispute] = $this->activeDisputeScenario();

        $this->actingAs($customer)
            ->post(route('customer.disputes.messages.store', $dispute), [
                'body' => 'Позвони +7 999 111 22 33',
            ]);

        $this->assertDatabaseHas('moderation_flags', [
            'user_id' => $customer->id,
            'entity_type' => DisputeMessage::class,
            'reason' => 'contact_detected_in_dispute_message',
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }

    public function test_customer_can_write_message_in_own_dispute(): void
    {
        [$customer, , , $dispute] = $this->activeDisputeScenario();

        $this->actingAs($customer)
            ->post(route('customer.disputes.messages.store', $dispute), ['body' => 'Прошу проверить сдачу работы.'])
            ->assertRedirect();

        $this->assertDatabaseHas('dispute_messages', [
            'dispute_id' => $dispute->id,
            'user_id' => $customer->id,
            'body' => 'Прошу проверить сдачу работы.',
        ]);
    }

    public function test_performer_can_write_message_in_own_dispute(): void
    {
        [, $performer, , $dispute] = $this->activeDisputeScenario();

        $this->actingAs($performer)
            ->post(route('performer.disputes.messages.store', $dispute), ['body' => 'Передал результат в рабочей области.'])
            ->assertRedirect();

        $this->assertDatabaseHas('dispute_messages', [
            'dispute_id' => $dispute->id,
            'user_id' => $performer->id,
            'body' => 'Передал результат в рабочей области.',
        ]);
    }

    public function test_moderator_can_write_message_in_dispute(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [, , , $dispute] = $this->activeDisputeScenario();

        $this->actingAs($moderator)
            ->post(route('moderator.disputes.messages.store', $dispute), ['body' => 'Запросил дополнительные материалы.'])
            ->assertRedirect();

        $this->assertDatabaseHas('dispute_messages', [
            'dispute_id' => $dispute->id,
            'user_id' => $moderator->id,
        ]);
    }

    public function test_moderator_sees_open_disputes_list(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [, , , $dispute] = $this->activeDisputeScenario();

        $response = $this->actingAs($moderator)
            ->get(route('moderator.disputes.index'))
            ->assertOk();

        $this->assertTrue(collect($response->inertiaProps('disputes'))->pluck('id')->contains($dispute->id));
    }

    public function test_admin_sees_open_disputes_list(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [, , , $dispute] = $this->activeDisputeScenario();

        $response = $this->actingAs($admin)
            ->get(route('moderator.disputes.index'))
            ->assertOk();

        $this->assertTrue(collect($response->inertiaProps('disputes'))->pluck('id')->contains($dispute->id));
    }

    public function test_customer_and_performer_cannot_open_moderator_disputes_list(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        $this->actingAs($customer)
            ->get(route('moderator.disputes.index'))
            ->assertForbidden();

        $this->actingAs($performer)
            ->get(route('moderator.disputes.index'))
            ->assertForbidden();
    }

    public function test_moderator_can_take_dispute(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [, , , $dispute] = $this->activeDisputeScenario();

        $this->actingAs($moderator)
            ->post(route('moderator.disputes.take', $dispute))
            ->assertRedirect(route('moderator.disputes.show', $dispute));

        $this->assertDatabaseHas('order_events', [
            'order_id' => $dispute->order_id,
            'user_id' => $moderator->id,
            'type' => OrderEvent::TYPE_DISPUTE_UNDER_REVIEW,
        ]);
    }

    public function test_take_moves_dispute_to_under_review(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [, , , $dispute] = $this->activeDisputeScenario();

        $this->actingAs($moderator)
            ->post(route('moderator.disputes.take', $dispute));

        $this->assertSame(Dispute::STATUS_UNDER_REVIEW, $dispute->refresh()->status);
    }

    public function test_moderator_can_resolve_dispute_release_to_performer(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [, , , $dispute] = $this->activeDisputeScenario(status: Dispute::STATUS_UNDER_REVIEW);

        $this->resolveDispute($moderator, $dispute, Dispute::RESOLUTION_RELEASE_TO_PERFORMER);

        $this->assertSame(Dispute::STATUS_RESOLVED, $dispute->refresh()->status);
        $this->assertSame(Dispute::RESOLUTION_RELEASE_TO_PERFORMER, $dispute->resolution);
        $this->assertSame($moderator->id, $dispute->resolved_by);
    }

    public function test_release_to_performer_completes_order_and_releases_payment(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [, , $order, $dispute] = $this->activeDisputeScenario(status: Dispute::STATUS_UNDER_REVIEW);

        $this->resolveDispute($moderator, $dispute, Dispute::RESOLUTION_RELEASE_TO_PERFORMER);

        $order->refresh();

        $this->assertSame(Order::STATUS_COMPLETED, $order->status);
        $this->assertSame(Order::PAYMENT_RELEASED, $order->payment_status);
        $this->assertSame(Order::RELEASE_DISPUTE_TO_PERFORMER, $order->release_reason);
    }

    public function test_moderator_can_resolve_dispute_refund_to_customer(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [, , , $dispute] = $this->activeDisputeScenario(status: Dispute::STATUS_UNDER_REVIEW);

        $this->resolveDispute($moderator, $dispute, Dispute::RESOLUTION_REFUND_TO_CUSTOMER);

        $this->assertSame(Dispute::STATUS_RESOLVED, $dispute->refresh()->status);
        $this->assertSame(Dispute::RESOLUTION_REFUND_TO_CUSTOMER, $dispute->resolution);
    }

    public function test_refund_to_customer_cancels_order_and_refunds_payment(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [, , $order, $dispute] = $this->activeDisputeScenario(status: Dispute::STATUS_UNDER_REVIEW);

        $this->resolveDispute($moderator, $dispute, Dispute::RESOLUTION_REFUND_TO_CUSTOMER);

        $order->refresh();

        $this->assertSame(Order::STATUS_CANCELED, $order->status);
        $this->assertSame(Order::PAYMENT_REFUNDED, $order->payment_status);
    }

    public function test_moderator_can_resolve_dispute_return_to_revision(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [, , , $dispute] = $this->activeDisputeScenario(status: Dispute::STATUS_UNDER_REVIEW);

        $this->resolveDispute($moderator, $dispute, Dispute::RESOLUTION_RETURN_TO_REVISION);

        $this->assertSame(Dispute::STATUS_RESOLVED, $dispute->refresh()->status);
        $this->assertSame(Dispute::RESOLUTION_RETURN_TO_REVISION, $dispute->resolution);
    }

    public function test_return_to_revision_moves_order_to_revision_requested_and_keeps_payment_held(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [, , $order, $dispute] = $this->activeDisputeScenario(status: Dispute::STATUS_UNDER_REVIEW);

        $this->resolveDispute($moderator, $dispute, Dispute::RESOLUTION_RETURN_TO_REVISION);

        $order->refresh();

        $this->assertSame(Order::STATUS_REVISION_REQUESTED, $order->status);
        $this->assertSame(Order::PAYMENT_HELD, $order->payment_status);
    }

    public function test_resolve_requires_moderator_comment(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [, , , $dispute] = $this->activeDisputeScenario(status: Dispute::STATUS_UNDER_REVIEW);

        $this->actingAs($moderator)
            ->post(route('moderator.disputes.resolve', $dispute), [
                'resolution' => Dispute::RESOLUTION_RELEASE_TO_PERFORMER,
                'moderator_comment' => '',
            ])
            ->assertSessionHasErrors('moderator_comment');
    }

    public function test_release_due_command_does_not_release_disputed_order(): void
    {
        [, , $order] = $this->orderScenario([
            'status' => Order::STATUS_DISPUTED,
            'payment_status' => Order::PAYMENT_HELD,
            'review_hold_until' => now()->subMinute(),
            'auto_release_at' => now()->subMinute(),
        ]);

        $this->artisan('orders:release-due')
            ->expectsOutput('Разблокировано заказов: 0')
            ->assertExitCode(0);

        $this->assertSame(Order::STATUS_DISPUTED, $order->refresh()->status);
        $this->assertSame(Order::PAYMENT_HELD, $order->payment_status);
    }

    public function test_release_due_command_does_not_release_order_with_active_dispute(): void
    {
        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_HELD,
            'review_hold_until' => now()->subMinute(),
            'auto_release_at' => now()->subMinute(),
        ]);
        $this->createDispute($order, $customer);

        $this->artisan('orders:release-due')
            ->expectsOutput('Разблокировано заказов: 0')
            ->assertExitCode(0);

        $this->assertSame(Order::STATUS_SUBMITTED_FOR_REVIEW, $order->refresh()->status);
        $this->assertSame(Order::PAYMENT_HELD, $order->payment_status);
    }

    public function test_participant_sees_order_materials_on_dispute_page(): void
    {
        [$customer, $performer, $order, $dispute] = $this->activeDisputeScenario();
        OrderMessage::factory()->for($order)->for($customer, 'user')->create(['body' => 'Материал для проверки']);
        OrderFile::factory()->for($order)->for($customer, 'user')->create(['original_name' => 'brief.pdf']);
        OrderEvent::factory()->for($order)->for($customer, 'user')->create(['type' => OrderEvent::TYPE_WORK_SUBMITTED]);
        OrderSubmission::factory()->for($order)->for($performer, 'user')->create(['message' => 'Сдача работы']);

        $response = $this->actingAs($customer)
            ->get(route('customer.disputes.show', $dispute))
            ->assertOk();

        $this->assertSame('Материал для проверки', $response->inertiaProps('dispute.materials.messages.0.body'));
        $this->assertSame('brief.pdf', $response->inertiaProps('dispute.materials.files.0.original_name'));
        $this->assertSame(OrderEvent::TYPE_WORK_SUBMITTED, $response->inertiaProps('dispute.materials.events.0.type'));
        $this->assertSame('Сдача работы', $response->inertiaProps('dispute.materials.submissions.0.message'));
    }

    public function test_resolved_dispute_page_shows_resolution_and_comment(): void
    {
        [$customer, , , $dispute] = $this->activeDisputeScenario(status: Dispute::STATUS_RESOLVED, resolution: Dispute::RESOLUTION_REFUND_TO_CUSTOMER);

        $response = $this->actingAs($customer)
            ->get(route('customer.disputes.show', $dispute))
            ->assertOk();

        $this->assertSame(Dispute::RESOLUTION_REFUND_TO_CUSTOMER, $response->inertiaProps('dispute.resolution'));
        $this->assertSame('Решение принято после проверки материалов заказа.', $response->inertiaProps('dispute.moderator_comment'));
    }

    public function test_customer_workspace_shows_active_dispute_link(): void
    {
        [$customer, , $order, $dispute] = $this->activeDisputeScenario();

        $response = $this->actingAs($customer)
            ->get(route('customer.orders.workspace', $order))
            ->assertOk();

        $this->assertSame(route('customer.disputes.show', $dispute), $response->inertiaProps('order.active_dispute_url'));
    }

    public function test_performer_workspace_shows_active_dispute_link(): void
    {
        [, $performer, $order, $dispute] = $this->activeDisputeScenario();

        $response = $this->actingAs($performer)
            ->get(route('performer.orders.workspace', $order))
            ->assertOk();

        $this->assertSame(route('performer.disputes.show', $dispute), $response->inertiaProps('order.active_dispute_url'));
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{0: User, 1: User, 2: Order}
     */
    private function orderScenario(array $state = []): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()
            ->for($customer, 'customer')
            ->for($performer, 'performer')
            ->create(array_merge([
                'status' => Order::STATUS_IN_PROGRESS,
                'payment_status' => Order::PAYMENT_HELD,
                'review_hold_started_at' => now()->subDay(),
                'review_hold_until' => now()->addDays(9),
                'auto_release_at' => now()->addDays(9),
            ], $state));

        return [$customer, $performer, $order];
    }

    /**
     * @return array<string, string>
     */
    private function disputePayload(): array
    {
        return [
            'reason' => Dispute::REASON_POOR_QUALITY,
            'description' => 'Работа требует проверки модератором, результат не соответствует заданию.',
        ];
    }

    private function createDispute(
        Order $order,
        User $openedBy,
        string $status = Dispute::STATUS_OPEN,
        ?string $resolution = null,
    ): Dispute {
        $moderator = $resolution ? User::factory()->create(['role' => User::ROLE_MODERATOR]) : null;

        return Dispute::factory()
            ->for($order)
            ->for($openedBy, 'openedBy')
            ->create([
                'status' => $status,
                'previous_order_status' => Order::STATUS_IN_PROGRESS,
                'previous_payment_status' => Order::PAYMENT_HELD,
                'resolution' => $resolution,
                'resolved_by' => $moderator?->id,
                'resolved_at' => $resolution ? now() : null,
                'moderator_comment' => $resolution ? 'Решение принято после проверки материалов заказа.' : null,
            ]);
    }

    /**
     * @return array{0: User, 1: User, 2: Order, 3: Dispute}
     */
    private function activeDisputeScenario(
        string $status = Dispute::STATUS_OPEN,
        ?string $resolution = null,
    ): array {
        [$customer, $performer, $order] = $this->orderScenario([
            'status' => $resolution ? Order::STATUS_COMPLETED : Order::STATUS_DISPUTED,
            'payment_status' => $resolution ? Order::PAYMENT_RELEASED : Order::PAYMENT_HELD,
            'review_hold_until' => null,
            'auto_release_at' => null,
        ]);
        $dispute = $this->createDispute($order, $customer, $status, $resolution);

        return [$customer, $performer, $order, $dispute];
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
