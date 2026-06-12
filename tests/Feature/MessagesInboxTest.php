<?php

namespace Tests\Feature;

use App\Models\ConversationRead;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\ModerationFlag;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class MessagesInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_messages_inbox(): void
    {
        $this->get(route('messages.index'))
            ->assertRedirect('/login');
    }

    public function test_customer_sees_only_own_order_conversations(): void
    {
        [$customer, $performer, $order] = $this->orderScenario(title: 'Личный заказ по дизайну');
        [, $foreignPerformer, $foreignOrder] = $this->orderScenario(title: 'Чужой заказ');

        OrderMessage::factory()->for($order)->for($performer, 'user')->create(['body' => 'Сообщение в личном заказе']);
        OrderMessage::factory()->for($foreignOrder)->for($foreignPerformer, 'user')->create(['body' => 'Сообщение в чужом заказе']);

        $response = $this->actingAs($customer)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Messages/Index')
                ->has('conversations', 1)
                ->where('conversations.0.type', 'order')
                ->where('conversations.0.id', $order->id)
                ->where('tabs.1.label', 'Непрочитанные'));

        $this->assertSame(['Личный заказ по дизайну'], collect($response->inertiaProps('conversations'))->pluck('title')->all());
    }

    public function test_admin_does_not_see_direct_order_chat_without_dispute(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [, $performer, $order] = $this->orderScenario(title: 'Приватный заказ без спора');
        OrderMessage::factory()->for($order)->for($performer, 'user')->create();

        $response = $this->actingAs($admin)
            ->get(route('messages.index'))
            ->assertOk();

        $this->assertSame([], $response->inertiaProps('conversations'));
    }

    public function test_admin_sees_dispute_conversation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_DISPUTED,
            'payment_status' => Order::PAYMENT_HELD,
        ], 'Спорный заказ');
        $dispute = $this->createDispute($order, $customer);
        DisputeMessage::factory()->for($dispute)->for($customer, 'user')->create(['body' => 'Нужна проверка модератора']);

        $response = $this->actingAs($admin)
            ->get(route('messages.index', ['tab' => 'disputes']))
            ->assertOk();

        $this->assertSame('dispute', $response->inertiaProps('conversations.0.type'));
        $this->assertSame($dispute->id, $response->inertiaProps('conversations.0.id'));
    }

    public function test_shared_unread_messages_count_excludes_own_messages(): void
    {
        [$customer, $performer, $order] = $this->orderScenario();
        OrderMessage::factory()->for($order)->for($customer, 'user')->create(['body' => 'Мое сообщение']);
        OrderMessage::factory()->for($order)->for($performer, 'user')->create(['body' => 'Ответ исполнителя']);

        $this->actingAs($customer)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('messages.unread_count', 1)
                ->where('conversations.0.unread_count', 1));
    }

    public function test_opening_order_conversation_marks_it_as_read(): void
    {
        [$customer, $performer, $order] = $this->orderScenario();
        OrderMessage::factory()->for($order)->for($performer, 'user')->create();

        $this->actingAs($customer)
            ->get(route('messages.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Messages/OrderShow')
                ->where('messages.unread_count', 0)
                ->where('conversation.id', $order->id));

        $this->assertDatabaseHas('conversation_reads', [
            'user_id' => $customer->id,
            'conversation_type' => ConversationRead::TYPE_ORDER,
            'conversation_id' => $order->id,
        ]);
    }

    public function test_order_participant_can_send_message_from_unified_conversation(): void
    {
        [$customer, $performer, $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('messages.orders.store', $order), [
                'body' => 'Обсуждаем детали заказа внутри Таскоры.',
            ])
            ->assertRedirect(route('messages.orders.show', $order));

        $this->assertDatabaseHas('order_messages', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'body' => 'Обсуждаем детали заказа внутри Таскоры.',
        ]);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'type' => OrderEvent::TYPE_MESSAGE_SENT,
        ]);
        $this->assertNotification($performer, 'order.message_sent');
    }

    public function test_contact_guard_blocks_order_message_from_unified_conversation(): void
    {
        [$customer, , $order] = $this->orderScenario();

        $this->actingAs($customer)
            ->post(route('messages.orders.store', $order), [
                'body' => 'Напиши мне на test@example.com',
            ])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('order_messages', 0);
        $this->assertDatabaseHas('moderation_flags', [
            'user_id' => $customer->id,
            'entity_type' => OrderMessage::class,
            'reason' => 'contact_detected_in_order_message',
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }

    public function test_foreign_user_cannot_open_or_write_order_conversation(): void
    {
        $foreign = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        [, , $order] = $this->orderScenario();

        $this->actingAs($foreign)
            ->get(route('messages.orders.show', $order))
            ->assertForbidden();

        $this->actingAs($foreign)
            ->post(route('messages.orders.store', $order), ['body' => 'Чужой доступ'])
            ->assertForbidden();
    }

    public function test_customer_and_moderator_can_open_dispute_conversation(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_DISPUTED,
            'payment_status' => Order::PAYMENT_HELD,
        ]);
        $dispute = $this->createDispute($order, $customer);

        $this->actingAs($customer)
            ->get(route('messages.disputes.show', $dispute))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Messages/DisputeShow')
                ->where('conversation.id', $dispute->id));

        $this->actingAs($moderator)
            ->get(route('messages.disputes.show', $dispute))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('conversation.id', $dispute->id));
    }

    public function test_foreign_user_cannot_open_dispute_conversation(): void
    {
        $foreign = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_DISPUTED,
            'payment_status' => Order::PAYMENT_HELD,
        ]);
        $dispute = $this->createDispute($order, $customer);

        $this->actingAs($foreign)
            ->get(route('messages.disputes.show', $dispute))
            ->assertForbidden();
    }

    public function test_user_can_send_dispute_message_from_unified_conversation(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_DISPUTED,
            'payment_status' => Order::PAYMENT_HELD,
        ]);
        $dispute = $this->createDispute($order, $customer);

        $this->actingAs($moderator)
            ->post(route('messages.disputes.store', $dispute), [
                'body' => 'Запросил дополнительные материалы по спору.',
            ])
            ->assertRedirect(route('messages.disputes.show', $dispute));

        $this->assertDatabaseHas('dispute_messages', [
            'dispute_id' => $dispute->id,
            'user_id' => $moderator->id,
            'body' => 'Запросил дополнительные материалы по спору.',
        ]);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'user_id' => $moderator->id,
            'type' => OrderEvent::TYPE_DISPUTE_MESSAGE_SENT,
        ]);
    }

    public function test_contact_guard_blocks_dispute_message_from_unified_conversation(): void
    {
        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_DISPUTED,
            'payment_status' => Order::PAYMENT_HELD,
        ]);
        $dispute = $this->createDispute($order, $customer);

        $this->actingAs($customer)
            ->post(route('messages.disputes.store', $dispute), [
                'body' => 'Позвони +7 999 111 22 33',
            ])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('dispute_messages', 0);
        $this->assertDatabaseHas('moderation_flags', [
            'user_id' => $customer->id,
            'entity_type' => DisputeMessage::class,
            'reason' => 'contact_detected_in_dispute_message',
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }

    public function test_inbox_filters_by_tabs_status_search_and_sort(): void
    {
        [$customer, $performer, $order] = $this->orderScenario([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ], 'Разработка лендинга');
        OrderMessage::factory()->for($order)->for($performer, 'user')->create(['body' => 'Новый ответ']);

        $dispute = $this->createDispute($order, $customer);
        DisputeMessage::factory()->for($dispute)->for($performer, 'user')->create(['body' => 'Сообщение по спору']);

        $ordersResponse = $this->actingAs($customer)->get(route('messages.index', ['tab' => 'orders']))->assertOk();
        $this->assertSame(['order'], collect($ordersResponse->inertiaProps('conversations'))->pluck('type')->all());

        $disputesResponse = $this->actingAs($customer)->get(route('messages.index', ['tab' => 'disputes']))->assertOk();
        $this->assertSame(['dispute'], collect($disputesResponse->inertiaProps('conversations'))->pluck('type')->all());

        $searchResponse = $this->actingAs($customer)->get(route('messages.index', ['q' => 'лендинга']))->assertOk();
        $this->assertCount(2, $searchResponse->inertiaProps('conversations'));

        $statusResponse = $this->actingAs($customer)->get(route('messages.index', ['status' => Order::STATUS_COMPLETED]))->assertOk();
        $this->assertSame([], $statusResponse->inertiaProps('conversations'));

        $unreadResponse = $this->actingAs($customer)->get(route('messages.index', ['tab' => 'unread', 'sort' => 'unread']))->assertOk();
        $this->assertNotEmpty($unreadResponse->inertiaProps('conversations'));
        $this->assertGreaterThan(0, $unreadResponse->inertiaProps('conversations.0.unread_count'));
    }

    public function test_inbox_empty_state_props_distinguish_no_dialogs_and_filtered_results(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $emptyResponse = $this->actingAs($admin)
            ->get(route('messages.index'))
            ->assertOk();

        $this->assertSame([], $emptyResponse->inertiaProps('conversations'));
        $this->assertSame(0, $emptyResponse->inertiaProps('filters.active_count'));

        [$customer, $performer, $order] = $this->orderScenario(title: 'Длинный заказ для проверки поиска');
        OrderMessage::factory()->for($order)->for($performer, 'user')->create(['body' => 'Есть активный диалог']);

        $filteredResponse = $this->actingAs($customer)
            ->get(route('messages.index', ['q' => 'нет-такого-диалога']))
            ->assertOk();

        $this->assertSame([], $filteredResponse->inertiaProps('conversations'));
        $this->assertSame('нет-такого-диалога', $filteredResponse->inertiaProps('filters.q'));
        $this->assertSame(1, $filteredResponse->inertiaProps('filters.active_count'));
    }

    public function test_customer_search_does_not_match_participant_email(): void
    {
        [$customer, $performer, $order] = $this->orderScenario(title: 'Заказ с приватным email');
        $performer->forceFill(['email' => 'hidden-performer@example.com'])->save();
        OrderMessage::factory()->for($order)->for($performer, 'user')->create();

        $response = $this->actingAs($customer)
            ->get(route('messages.index', ['q' => 'hidden-performer@example.com']))
            ->assertOk();

        $this->assertSame([], $response->inertiaProps('conversations'));
    }

    public function test_messages_props_do_not_expose_sensitive_user_fields(): void
    {
        [$customer, $performer, $order] = $this->orderScenario();
        OrderMessage::factory()->for($order)->for($performer, 'user')->create();

        $response = $this->actingAs($customer)
            ->get(route('messages.orders.show', $order))
            ->assertOk();

        $payload = json_encode($response->inertiaProps('conversation'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('password', $payload);
        $this->assertStringNotContainsString('remember_token', $payload);
        $this->assertStringNotContainsString('reset', $payload);
    }

    public function test_order_conversation_v2_payload_contains_context_without_private_file_paths(): void
    {
        [$customer, $performer, $order] = $this->orderScenario([
            'platform_fee_amount' => 750,
            'performer_amount' => 4250,
            'delivery_days' => 5,
        ], 'Дизайн презентации');
        OrderMessage::factory()->for($order)->for($performer, 'user')->create(['body' => 'Готовлю первый вариант.']);
        OrderFile::factory()
            ->for($order)
            ->for($customer, 'user')
            ->create([
                'original_name' => 'brief.txt',
                'stored_name' => 'secret-stored-name.txt',
                'path' => 'orders/'.$order->id.'/secret-stored-name.txt',
                'size' => 2048,
            ]);
        OrderEvent::factory()
            ->for($order)
            ->for($customer, 'user')
            ->create([
                'type' => OrderEvent::TYPE_FILE_UPLOADED,
                'payload' => ['file_name' => 'brief.txt'],
            ]);

        $response = $this->actingAs($customer)
            ->get(route('messages.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Messages/OrderShow')
                ->where('conversation.key', 'order-'.$order->id)
                ->where('conversation.type', 'order')
                ->where('conversation.platform_fee_amount', 750)
                ->where('conversation.performer_amount', 4250)
                ->where('conversation.delivery_days', 5)
                ->where('conversation.files.0.original_name', 'brief.txt')
                ->where('conversation.files.0.status_label', 'Доступен')
                ->where('conversation.timeline_events.0.label', 'Файл загружен')
                ->where('conversation.messages.0.date_label', 'Сегодня')
                ->has('conversations', 1));

        $payload = json_encode($response->inertiaProps('conversation'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('stored_name', $payload);
        $this->assertStringNotContainsString('secret-stored-name.txt', $payload);
        $this->assertStringNotContainsString('orders/'.$order->id, $payload);
        $this->assertStringNotContainsString('/storage/', $payload);
    }

    public function test_dispute_conversation_v2_payload_contains_dispute_and_order_context(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_DISPUTED,
            'payment_status' => Order::PAYMENT_HELD,
        ], 'Спорный заказ с файлами');
        $dispute = $this->createDispute($order, $customer);
        DisputeMessage::factory()->for($dispute)->for($customer, 'user')->create(['body' => 'Нужно решение по спору.']);

        $this->actingAs($moderator)
            ->get(route('messages.disputes.show', $dispute))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Messages/DisputeShow')
                ->where('conversation.key', 'dispute-'.$dispute->id)
                ->where('conversation.type', 'dispute')
                ->where('conversation.reason_label', Dispute::reasonLabels()[$dispute->reason])
                ->where('conversation.order.title', 'Спорный заказ с файлами')
                ->where('conversation.order.payment_status_label', Order::paymentStatusLabels()[Order::PAYMENT_HELD])
                ->where('conversation.messages.0.date_label', 'Сегодня')
                ->has('conversations', 1));
    }

    public function test_messages_navigation_and_ui_source_are_present(): void
    {
        $dashboardLayout = file_get_contents(resource_path('js/Layouts/DashboardLayout.jsx'));
        $publicLayout = file_get_contents(resource_path('js/Layouts/PublicLayout.jsx'));
        $indexPage = file_get_contents(resource_path('js/Pages/Messages/Index.jsx'));
        $orderPage = file_get_contents(resource_path('js/Pages/Messages/OrderShow.jsx'));
        $disputePage = file_get_contents(resource_path('js/Pages/Messages/DisputeShow.jsx'));
        $messengerLayout = file_get_contents(resource_path('js/Pages/Messages/Partials/MessengerLayout.jsx'));
        $source = $indexPage.$orderPage.$disputePage.$messengerLayout;

        $this->assertStringContainsString('/messages', $dashboardLayout);
        $this->assertStringContainsString('Сообщения', $dashboardLayout);
        $this->assertStringContainsString('/messages', $publicLayout);
        $this->assertStringContainsString('messages-layout', $source);
        $this->assertStringContainsString('Вкладки сообщений', $source);
        $this->assertStringContainsString('Поиск по диалогам', $source);
        $this->assertStringContainsString('messages-filters-toggle', $source);
        $this->assertStringContainsString('Показать фильтры', $source);
        $this->assertStringContainsString('Скрыть фильтры', $source);
        $this->assertStringContainsString('Сбросить фильтры', $source);
        $this->assertStringContainsString('Диалогов по этому запросу нет.', $source);
        $this->assertStringContainsString('У вас пока нет переписок.', $source);
        $this->assertStringContainsString('Выберите переписку', $source);
        $this->assertStringContainsString('Открыть последний диалог', $source);
        $this->assertStringContainsString('Назад к сообщениям', $source);
        $this->assertStringContainsString('Детали заказа', $source);
        $this->assertStringContainsString('Работайте и передавайте материалы только внутри Таскоры', $source);
        $this->assertStringContainsString('Напишите сообщение…', $source);
        $this->assertStringContainsString('aria-label="Текст сообщения"', $source);
        $this->assertStringContainsString('Отправить сообщение', $source);
        $this->assertStringContainsString('role="alert"', $source);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{0: User, 1: User, 2: Order}
     */
    private function orderScenario(array $state = [], string $title = 'Тестовый заказ'): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()
            ->for($customer, 'customer')
            ->for($performer, 'performer')
            ->create(array_merge([
                'title' => $title,
                'status' => Order::STATUS_IN_PROGRESS,
                'payment_status' => Order::PAYMENT_HELD,
            ], $state));

        return [$customer, $performer, $order];
    }

    private function createDispute(Order $order, User $openedBy): Dispute
    {
        return Dispute::factory()
            ->for($order)
            ->for($openedBy, 'openedBy')
            ->create([
                'status' => Dispute::STATUS_OPEN,
                'previous_order_status' => Order::STATUS_IN_PROGRESS,
                'previous_payment_status' => Order::PAYMENT_HELD,
            ]);
    }

    private function assertNotification(User $user, string $type): DatabaseNotification
    {
        $notification = $user->notifications()
            ->get()
            ->first(fn (DatabaseNotification $notification): bool => ($notification->data['type'] ?? null) === $type);

        $this->assertInstanceOf(DatabaseNotification::class, $notification, "Notification {$type} was not created.");

        return $notification;
    }
}
