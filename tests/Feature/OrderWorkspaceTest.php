<?php

namespace Tests\Feature;

use App\Models\ModerationFlag;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\OrderSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_owner_can_open_customer_workspace(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->get(route('customer.orders.workspace', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/Workspace')
                ->where('role', 'customer')
                ->where('order.id', $order->id)
            );
    }

    public function test_performer_owner_can_open_performer_workspace(): void
    {
        [, $performer, $order] = $this->orderScenario();

        $this->actingAs($performer)
            ->get(route('performer.orders.workspace', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Orders/Workspace')
                ->where('role', 'performer')
                ->where('order.id', $order->id)
            );
    }

    public function test_guest_cannot_open_workspace(): void
    {
        [, , $order] = $this->orderScenario();

        $this->get(route('customer.orders.workspace', $order))
            ->assertRedirect('/login');
    }

    public function test_customer_cannot_open_foreign_workspace(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        [, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->get(route('customer.orders.workspace', $order))
            ->assertForbidden();
    }

    public function test_performer_cannot_open_foreign_workspace(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        [, , $order] = $this->orderScenario();

        $this->actingAs($performer)
            ->get(route('performer.orders.workspace', $order))
            ->assertForbidden();
    }

    public function test_customer_can_send_message_to_own_order(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.messages.store', $order), [
                'body' => 'Здравствуйте, уточняю детали заказа внутри Таскоры.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_messages', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'body' => 'Здравствуйте, уточняю детали заказа внутри Таскоры.',
        ]);
    }

    public function test_performer_can_send_message_to_own_order(): void
    {
        [, $performer, $order] = $this->orderScenario();

        $this->actingAs($performer)
            ->post(route('performer.orders.messages.store', $order), [
                'body' => 'Принял заказ, подготовлю материалы по заданию.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_messages', [
            'order_id' => $order->id,
            'user_id' => $performer->id,
            'body' => 'Принял заказ, подготовлю материалы по заданию.',
        ]);
    }

    public function test_message_with_email_phone_or_telegram_is_blocked(): void
    {
        [$customer, , $order] = $this->orderScenario();

        foreach (['mail me test@example.com', 'позвони +7 999 111 22 33', 'напиши в тг taskora_user'] as $body) {
            $this->actingAs($customer)
                ->post(route('customer.orders.messages.store', $order), ['body' => $body])
                ->assertSessionHasErrors('body');
        }

        $this->assertDatabaseCount('order_messages', 0);
    }

    public function test_blocked_message_creates_moderation_flag(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.messages.store', $order), [
                'body' => 'Напиши на test@example.com',
            ]);

        $this->assertDatabaseHas('moderation_flags', [
            'user_id' => $customer->id,
            'entity_type' => OrderMessage::class,
            'reason' => 'contact_detected_in_order_message',
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }

    public function test_blocked_message_creates_contact_blocked_event(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.messages.store', $order), [
                'body' => 'Напиши на test@example.com',
            ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_CONTACT_BLOCKED,
        ]);
    }

    public function test_sent_message_is_displayed_in_workspace(): void
    {
        [$customer, , $order] = $this->orderScenario();
        OrderMessage::factory()->for($order)->for($customer, 'user')->create([
            'body' => 'Сообщение видно в рабочей области.',
        ]);

        $response = $this->actingAs($customer)
            ->get(route('customer.orders.workspace', $order))
            ->assertOk();

        $this->assertTrue(
            collect($response->inertiaProps('order.messages'))->pluck('body')->contains('Сообщение видно в рабочей области.'),
        );
    }

    public function test_message_sent_creates_message_sent_event(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.messages.store', $order), [
                'body' => 'Событие сообщения должно появиться в истории.',
            ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_MESSAGE_SENT,
        ]);
    }

    public function test_customer_can_upload_allowed_file(): void
    {
        Storage::fake('local');
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.files.store', $order), [
                'file' => UploadedFile::fake()->create('brief.pdf', 12, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_files', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'original_name' => 'brief.pdf',
            'disk' => 'local',
            'status' => OrderFile::STATUS_AVAILABLE,
        ]);
    }

    public function test_performer_can_upload_allowed_file(): void
    {
        Storage::fake('local');
        [, $performer, $order] = $this->orderScenario();

        $this->actingAs($performer)
            ->post(route('performer.orders.files.store', $order), [
                'file' => UploadedFile::fake()->create('result.pdf', 12, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('order_files', [
            'order_id' => $order->id,
            'user_id' => $performer->id,
            'original_name' => 'result.pdf',
            'disk' => 'local',
        ]);
    }

    public function test_file_is_stored_in_private_storage(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.files.store', $order), [
                'file' => UploadedFile::fake()->create('private.pdf', 12, 'application/pdf'),
            ]);

        $file = OrderFile::firstOrFail();

        Storage::disk('local')->assertExists($file->path);
        Storage::disk('public')->assertMissing($file->path);
        $this->assertStringStartsWith("orders/{$order->id}/", $file->path);
    }

    public function test_foreign_user_cannot_download_file(): void
    {
        Storage::fake('local');
        $foreignCustomer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        [, , $order] = $this->orderScenario();
        $file = $this->storedOrderFile($order);

        $this->actingAs($foreignCustomer)
            ->get(route('customer.orders.files.download', [$order, $file]))
            ->assertForbidden();
    }

    public function test_order_participant_can_download_file(): void
    {
        Storage::fake('local');
        [$customer, , $order] = $this->orderScenario();
        $file = $this->storedOrderFile($order);

        $this->actingAs($customer)
            ->get(route('customer.orders.files.download', [$order, $file]))
            ->assertOk();
    }

    public function test_disallowed_file_type_fails_validation(): void
    {
        Storage::fake('local');
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.files.store', $order), [
                'file' => UploadedFile::fake()->create('script.exe', 12, 'application/x-msdownload'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('order_files', 0);
    }

    public function test_too_large_file_fails_validation(): void
    {
        Storage::fake('local');
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.files.store', $order), [
                'file' => UploadedFile::fake()->create('large.pdf', 20481, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('order_files', 0);
    }

    public function test_file_name_with_contact_is_blocked(): void
    {
        Storage::fake('local');
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.files.store', $order), [
                'file' => UploadedFile::fake()->create('brief-test@example.com.txt', 1, 'text/plain'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('order_files', 0);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'type' => OrderEvent::TYPE_CONTACT_BLOCKED,
        ]);
    }

    public function test_file_upload_creates_file_uploaded_event(): void
    {
        Storage::fake('local');
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.files.store', $order), [
                'file' => UploadedFile::fake()->create('brief.pdf', 12, 'application/pdf'),
            ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_FILE_UPLOADED,
        ]);
    }

    public function test_mark_paid_creates_payment_stub_paid_event(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.mark-paid', $order));

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_PAYMENT_STUB_PAID,
        ]);
    }

    public function test_submit_work_creates_work_submitted_event(): void
    {
        [, $performer, $order] = $this->orderScenario([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $this->actingAs($performer)
            ->post(route('performer.orders.submit-work', $order), [
                'message' => 'Работа готова.',
            ]);

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $performer->id,
            'type' => OrderEvent::TYPE_WORK_SUBMITTED,
        ]);
    }

    public function test_request_revision_creates_revision_requested_event(): void
    {
        [$customer, $performer, $order] = $this->submittedOrderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.request-revision', $order));

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_REVISION_REQUESTED,
        ]);
        $this->assertSame($performer->id, $order->submissions()->first()->user_id);
    }

    public function test_complete_creates_order_completed_event(): void
    {
        [$customer, , $order] = $this->submittedOrderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.complete', $order));

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_ORDER_COMPLETED,
        ]);
    }

    public function test_cancel_creates_order_canceled_event(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('customer.orders.cancel', $order));

        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_ORDER_CANCELED,
        ]);
    }

    public function test_workspace_shows_system_events(): void
    {
        [$customer, , $order] = $this->orderScenario();
        OrderEvent::factory()->for($order)->for($customer, 'user')->create([
            'type' => OrderEvent::TYPE_PAYMENT_STUB_PAID,
        ]);

        $response = $this->actingAs($customer)
            ->get(route('customer.orders.workspace', $order))
            ->assertOk();

        $this->assertTrue(
            collect($response->inertiaProps('order.events'))->pluck('type')->contains(OrderEvent::TYPE_PAYMENT_STUB_PAID),
        );
    }

    public function test_customer_and_performer_see_same_order_messages_and_files(): void
    {
        Storage::fake('local');
        [$customer, $performer, $order] = $this->orderScenario();
        $message = OrderMessage::factory()->for($order)->for($customer, 'user')->create();
        $file = $this->storedOrderFile($order, $customer);

        $customerResponse = $this->actingAs($customer)
            ->get(route('customer.orders.workspace', $order))
            ->assertOk();

        $performerResponse = $this->actingAs($performer)
            ->get(route('performer.orders.workspace', $order))
            ->assertOk();

        $this->assertSame(
            [$message->id],
            collect($customerResponse->inertiaProps('order.messages'))->pluck('id')->all(),
        );
        $this->assertSame(
            [$message->id],
            collect($performerResponse->inertiaProps('order.messages'))->pluck('id')->all(),
        );
        $this->assertSame(
            [$file->id],
            collect($customerResponse->inertiaProps('order.files'))->pluck('id')->all(),
        );
        $this->assertSame(
            [$file->id],
            collect($performerResponse->inertiaProps('order.files'))->pluck('id')->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $orderState
     * @return array{0: User, 1: User, 2: Order}
     */
    private function orderScenario(array $orderState = []): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()
            ->for($customer, 'customer')
            ->for($performer, 'performer')
            ->create($orderState);

        return [$customer, $performer, $order];
    }

    /**
     * @return array{0: User, 1: User, 2: Order}
     */
    private function submittedOrderScenario(): array
    {
        [$customer, $performer, $order] = $this->orderScenario([
            'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        OrderSubmission::factory()->for($order)->for($performer, 'user')->create([
            'status' => OrderSubmission::STATUS_SUBMITTED,
        ]);

        return [$customer, $performer, $order];
    }

    private function storedOrderFile(Order $order, ?User $user = null): OrderFile
    {
        $user ??= $order->customer;
        $path = "orders/{$order->id}/stored.txt";
        Storage::disk('local')->put($path, 'private file');

        return OrderFile::factory()
            ->for($order)
            ->for($user, 'user')
            ->create([
                'original_name' => 'stored.txt',
                'stored_name' => 'stored.txt',
                'path' => $path,
                'disk' => 'local',
                'mime_type' => 'text/plain',
                'size' => 12,
            ]);
    }
}
