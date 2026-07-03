<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderSubmission;
use App\Models\ModerationFlag;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_order_from_published_service(): void
    {
        [$customer, $service, $package] = $this->serviceScenario();

        $response = $this->actingAs($customer)
            ->post(route('services.order.store', $service), ['package_id' => $package->id]);

        $order = Order::firstOrFail();

        $response->assertRedirect(route('customer.orders.show', $order));
        $this->assertSame(Order::SOURCE_SERVICE, $order->source_type);
        $this->assertSame(Order::STATUS_AWAITING_PAYMENT, $order->status);
        $this->assertSame(Order::PAYMENT_UNPAID, $order->payment_status);
        $this->assertSame($package->price, $order->price);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_ORDER_CREATED,
        ]);
    }

    public function test_guest_cannot_create_order_from_service(): void
    {
        [, $service, $package] = $this->serviceScenario();

        $this->post(route('services.order.store', $service), ['package_id' => $package->id])
            ->assertRedirect('/login');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_performer_cannot_create_order_from_service(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        [, $service, $package] = $this->serviceScenario();

        $this->actingAs($performer)
            ->post(route('services.order.store', $service), ['package_id' => $package->id])
            ->assertForbidden();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cannot_create_order_from_non_published_service(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        foreach ([Service::STATUS_DRAFT, Service::STATUS_PENDING_REVIEW, Service::STATUS_REJECTED, Service::STATUS_ARCHIVED] as $status) {
            $service = Service::factory()->for($performer, 'user')->create([
                'status' => $status,
                'slug' => fake()->unique()->slug(),
            ]);
            $package = ServicePackage::factory()->for($service)->create();

            $this->actingAs($customer)
                ->post(route('services.order.store', $service), ['package_id' => $package->id])
                ->assertNotFound();
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_can_accept_submitted_offer_and_create_order(): void
    {
        [$customer, $offer] = $this->offerScenario();

        $response = $this->actingAs($customer)
            ->post(route('customer.task-offers.accept', $offer));

        $order = Order::firstOrFail();

        $response->assertRedirect(route('customer.orders.show', $order));
        $this->assertSame(Order::SOURCE_TASK_OFFER, $order->source_type);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_ORDER_CREATED,
        ]);
    }

    public function test_cannot_accept_foreign_offer(): void
    {
        [, $offer] = $this->offerScenario();
        $foreignCustomer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($foreignCustomer)
            ->post(route('customer.task-offers.accept', $offer))
            ->assertForbidden();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cannot_accept_withdrawn_or_rejected_offer(): void
    {
        [$customer, $offer] = $this->offerScenario();

        foreach ([TaskOffer::STATUS_WITHDRAWN, TaskOffer::STATUS_REJECTED] as $status) {
            $offer->update(['status' => $status]);

            $this->actingAs($customer)
                ->post(route('customer.task-offers.accept', $offer))
                ->assertForbidden();
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_accepting_offer_creates_order(): void
    {
        [$customer, $offer] = $this->offerScenario();

        $this->actingAs($customer)
            ->post(route('customer.task-offers.accept', $offer))
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'task_offer_id' => $offer->id,
            'customer_id' => $customer->id,
            'performer_id' => $offer->user_id,
        ]);
    }

    public function test_accepting_offer_closes_task(): void
    {
        [$customer, $offer] = $this->offerScenario();

        $this->actingAs($customer)
            ->post(route('customer.task-offers.accept', $offer));

        $this->assertSame(Task::STATUS_CLOSED, $offer->task->refresh()->status);
    }

    public function test_accepting_offer_marks_offer_accepted(): void
    {
        [$customer, $offer] = $this->offerScenario();

        $this->actingAs($customer)
            ->post(route('customer.task-offers.accept', $offer));

        $this->assertSame(TaskOffer::STATUS_ACCEPTED, $offer->refresh()->status);
    }

    public function test_customer_sees_own_orders(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create(['title' => 'Заказ заказчика']);

        $response = $this->actingAs($customer)
            ->get(route('customer.orders.index'))
            ->assertOk();

        $this->assertTrue(
            collect($response->inertiaProps('orders'))->pluck('title')->contains($order->title),
        );
    }

    public function test_performer_sees_own_orders(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()->for($performer, 'performer')->create(['title' => 'Заказ исполнителя']);

        $response = $this->actingAs($performer)
            ->get(route('performer.orders.index'))
            ->assertOk();

        $this->assertTrue(
            collect($response->inertiaProps('orders'))->pluck('title')->contains($order->title),
        );
    }

    public function test_customer_does_not_see_foreign_order(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->create();

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertForbidden();
    }

    public function test_performer_does_not_see_foreign_order(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()->create();

        $this->actingAs($performer)
            ->get(route('performer.orders.show', $order))
            ->assertForbidden();
    }

    public function test_customer_can_mark_own_awaiting_payment_order_paid(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->post(route('customer.orders.mark-paid', $order))
            ->assertRedirect(route('customer.orders.show', $order));

        $this->assertNotNull($order->refresh()->started_at);
    }

    public function test_mark_paid_moves_order_to_in_progress_and_payment_held(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->post(route('customer.orders.mark-paid', $order));

        $this->assertSame(Order::STATUS_IN_PROGRESS, $order->refresh()->status);
        $this->assertSame(Order::PAYMENT_HELD, $order->payment_status);
    }

    public function test_performer_can_submit_work_for_review(): void
    {
        [$performer, $order] = $this->inProgressOrderForPerformer();

        $this->actingAs($performer)
            ->post(route('performer.orders.submit-work', $order), [
                'message' => 'Работа готова, результат можно проверить.',
            ])
            ->assertRedirect(route('performer.orders.show', $order));

        $this->assertDatabaseHas('order_submissions', [
            'order_id' => $order->id,
            'user_id' => $performer->id,
            'status' => OrderSubmission::STATUS_SUBMITTED,
        ]);
    }

    public function test_submit_work_moves_order_to_submitted_for_review(): void
    {
        [$performer, $order] = $this->inProgressOrderForPerformer();

        $this->actingAs($performer)
            ->post(route('performer.orders.submit-work', $order), [
                'message' => 'Работа готова.',
            ]);

        $this->assertSame(Order::STATUS_SUBMITTED_FOR_REVIEW, $order->refresh()->status);
        $this->assertNotNull($order->submitted_at);
    }

    public function test_customer_can_complete_work(): void
    {
        [$customer, $order] = $this->submittedOrderForCustomer();

        $this->actingAs($customer)
            ->post(route('customer.orders.complete', $order))
            ->assertRedirect(route('customer.orders.show', $order));

        $this->assertNotNull($order->refresh()->completed_at);
    }

    public function test_complete_moves_order_to_completed_and_payment_released(): void
    {
        [$customer, $order] = $this->submittedOrderForCustomer();

        $this->actingAs($customer)
            ->post(route('customer.orders.complete', $order));

        $this->assertSame(Order::STATUS_COMPLETED, $order->refresh()->status);
        $this->assertSame(Order::PAYMENT_RELEASED, $order->payment_status);
    }

    public function test_customer_can_request_revision(): void
    {
        [$customer, $order] = $this->submittedOrderForCustomer();

        $this->actingAs($customer)
            ->post(route('customer.orders.request-revision', $order), $this->revisionPayload())
            ->assertRedirect(route('customer.orders.show', $order));

        $this->assertSame(OrderSubmission::STATUS_REVISION_REQUESTED, $order->submissions()->first()->status);
    }

    public function test_request_revision_requires_comment(): void
    {
        [$customer, $order] = $this->submittedOrderForCustomer();

        $this->actingAs($customer)
            ->from(route('customer.orders.show', $order))
            ->post(route('customer.orders.request-revision', $order), [])
            ->assertRedirect(route('customer.orders.show', $order))
            ->assertSessionHasErrors(['revision_comment' => 'Опишите, что именно нужно исправить.']);

        $this->assertSame(Order::STATUS_SUBMITTED_FOR_REVIEW, $order->refresh()->status);
    }

    public function test_request_revision_blocks_contact_in_comment(): void
    {
        [$customer, $order] = $this->submittedOrderForCustomer();

        $this->actingAs($customer)
            ->from(route('customer.orders.show', $order))
            ->post(route('customer.orders.request-revision', $order), [
                'revision_comment' => 'Please call +7 999 123-45-67 before revision.',
            ])
            ->assertRedirect(route('customer.orders.show', $order))
            ->assertSessionHasErrors('revision_comment');

        $this->assertSame(Order::STATUS_SUBMITTED_FOR_REVIEW, $order->refresh()->status);
        $this->assertDatabaseHas('moderation_flags', [
            'user_id' => $customer->id,
            'entity_type' => Order::class,
            'entity_id' => $order->id,
            'reason' => 'contact_detected_in_revision_comment',
            'matched_type' => 'phone',
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }

    public function test_request_revision_moves_order_to_revision_requested(): void
    {
        [$customer, $order] = $this->submittedOrderForCustomer();

        $this->actingAs($customer)
            ->post(route('customer.orders.request-revision', $order), $this->revisionPayload());

        $this->assertSame(Order::STATUS_REVISION_REQUESTED, $order->refresh()->status);
    }

    public function test_customer_can_cancel_unpaid_awaiting_payment_order(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->post(route('customer.orders.cancel', $order))
            ->assertRedirect(route('customer.orders.show', $order));

        $this->assertSame(Order::STATUS_CANCELED, $order->refresh()->status);
        $this->assertSame(Order::PAYMENT_CANCELED, $order->payment_status);
    }

    public function test_cannot_cancel_completed_order(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->for($customer, 'customer')->completed()->create();

        $this->actingAs($customer)
            ->post(route('customer.orders.cancel', $order))
            ->assertForbidden();

        $this->assertSame(Order::STATUS_COMPLETED, $order->refresh()->status);
    }

    public function test_platform_fee_amounts_are_calculated_correctly(): void
    {
        [$customer, $service, $package] = $this->serviceScenario(packagePrice: 10000);

        $this->actingAs($customer)
            ->post(route('services.order.store', $service), ['package_id' => $package->id]);

        $order = Order::firstOrFail();

        $this->assertSame(1500, $order->platform_fee_amount);
        $this->assertSame(8500, $order->performer_amount);
    }

    public function test_platform_fee_percent_is_read_from_config_not_env(): void
    {
        config(['payments.platform_fee_percent' => 10]);

        [$customer, $service, $package] = $this->serviceScenario(packagePrice: 10000);

        $this->actingAs($customer)
            ->post(route('services.order.store', $service), ['package_id' => $package->id]);

        $order = Order::firstOrFail();

        $this->assertSame(10.0, (float) $order->platform_fee_percent);
        $this->assertSame(1000, $order->platform_fee_amount);
        $this->assertSame(9000, $order->performer_amount);
    }

    public function test_accepted_offer_fee_percent_is_read_from_config(): void
    {
        config(['payments.platform_fee_percent' => 20]);

        [$customer, $offer] = $this->offerScenario();

        $this->actingAs($customer)
            ->post(route('customer.task-offers.accept', $offer));

        $order = Order::firstOrFail();

        $this->assertSame(20.0, (float) $order->platform_fee_percent);
        $this->assertSame(1400, $order->platform_fee_amount);
        $this->assertSame(5600, $order->performer_amount);
    }

    public function test_submit_work_with_contacts_is_blocked_by_contact_guard(): void
    {
        [$performer, $order] = $this->inProgressOrderForPerformer();

        $this->actingAs($performer)
            ->from(route('performer.orders.show', $order))
            ->post(route('performer.orders.submit-work', $order), [
                'message' => 'Работа готова, напиши в тг @my_super_nick за исходниками.',
            ])
            ->assertRedirect(route('performer.orders.show', $order))
            ->assertSessionHasErrors('message');

        $this->assertDatabaseCount('order_submissions', 0);
        $this->assertSame(Order::STATUS_IN_PROGRESS, $order->refresh()->status);
        $this->assertDatabaseHas('moderation_flags', [
            'user_id' => $performer->id,
            'entity_type' => Order::class,
            'entity_id' => $order->id,
            'reason' => 'contact_detected_in_work_submission',
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }

    public function test_submit_work_without_contacts_passes_contact_guard(): void
    {
        [$performer, $order] = $this->inProgressOrderForPerformer();

        $this->actingAs($performer)
            ->post(route('performer.orders.submit-work', $order), [
                'message' => 'Работа готова, файлы приложены в рабочей области.',
            ]);

        $this->assertSame(Order::STATUS_SUBMITTED_FOR_REVIEW, $order->refresh()->status);
        $this->assertDatabaseCount('moderation_flags', 0);
    }

    public function test_dashboard_links_to_orders_are_displayed(): void
    {
        $customerDashboard = file_get_contents(resource_path('js/Pages/Dashboards/Customer.jsx'));
        $performerDashboard = file_get_contents(resource_path('js/Pages/Dashboards/Performer.jsx'));

        $this->assertStringContainsString('/customer/orders', $customerDashboard);
        $this->assertStringContainsString('/performer/orders', $performerDashboard);
    }

    /**
     * @return array{0: User, 1: Service, 2: ServicePackage}
     */
    private function serviceScenario(int $packagePrice = 5000): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $service = Service::factory()->for($performer, 'user')->create([
            'status' => Service::STATUS_PUBLISHED,
            'price_from' => 3000,
            'delivery_days' => 5,
        ]);
        $package = ServicePackage::factory()->for($service)->create([
            'price' => $packagePrice,
            'delivery_days' => 4,
            'description' => 'Пакет для тестового заказа.',
        ]);

        return [$customer, $service, $package];
    }

    /**
     * @return array{0: User, 1: TaskOffer}
     */
    private function offerScenario(): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $task = Task::factory()->for($customer, 'customer')->create([
            'status' => Task::STATUS_PUBLISHED,
        ]);
        $offer = TaskOffer::factory()->for($task)->for($performer, 'performer')->create([
            'status' => TaskOffer::STATUS_SUBMITTED,
            'price' => 7000,
            'delivery_days' => 6,
        ]);

        return [$customer, $offer];
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function inProgressOrderForPerformer(): array
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()->for($performer, 'performer')->inProgress()->create();

        return [$performer, $order];
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function submittedOrderForCustomer(): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()
            ->for($customer, 'customer')
            ->for($performer, 'performer')
            ->submittedForReview()
            ->create();

        OrderSubmission::factory()->for($order)->for($performer, 'user')->create([
            'status' => OrderSubmission::STATUS_SUBMITTED,
        ]);

        return [$customer, $order];
    }

    /**
     * @return array<string, string>
     */
    private function revisionPayload(string $comment = 'Please improve the final section and attach the corrected source file.'): array
    {
        return [
            'revision_comment' => $comment,
        ];
    }
}
