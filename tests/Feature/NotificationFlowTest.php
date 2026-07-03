<?php

namespace Tests\Feature;

use App\Models\Dispute;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;
use App\Notifications\PlatformNotification;
use App\Services\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_notifications_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Notifications/Index')
                ->has('items')
                ->where('unreadCount', 0));
    }

    public function test_notifications_page_has_readable_dark_theme_classes(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Notifications/Index.jsx'));

        $this->assertStringContainsString('dark:border-slate-800 dark:bg-slate-900', $source);
        $this->assertStringContainsString('dark:border-blue-700 dark:bg-blue-950/45', $source);
        $this->assertStringContainsString('dark:text-slate-300', $source);
        $this->assertStringContainsString('dark:bg-blue-500 dark:hover:bg-blue-400', $source);
        $this->assertStringContainsString('focus-visible:ring-blue-300', $source);
    }

    public function test_guest_cannot_view_notifications_page(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect('/login');
    }

    public function test_user_sees_unread_notifications_counter_in_shared_props(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $this->notify($user, 'test.one');
        $this->notify($user, 'test.two');

        $this->actingAs($user)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('notifications.unread_count', 2)
                ->has('notifications.latest', 2));
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $notification = $this->notify($user, 'test.read');

        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_user_cannot_mark_foreign_notification_as_read(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $other = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $notification = $this->notify($other, 'test.foreign');

        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->refresh()->read_at);
    }

    public function test_user_can_mark_all_own_notifications_as_read(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $other = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $this->notify($user, 'test.first');
        $this->notify($user, 'test.second');
        $foreign = $this->notify($other, 'test.foreign');

        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertNull($foreign->refresh()->read_at);
    }

    public function test_service_approval_creates_notification_for_performer(): void
    {
        [$moderator, $performer, $service] = $this->pendingServiceScenario();

        $this->actingAs($moderator)
            ->post(route('moderator.services.approve', $service))
            ->assertRedirect();

        $this->assertNotification($performer, 'service.approved');
    }

    public function test_service_rejection_creates_notification_for_performer(): void
    {
        [$moderator, $performer, $service] = $this->pendingServiceScenario();

        $this->actingAs($moderator)
            ->post(route('moderator.services.reject', $service), [
                'reason' => 'Нужно подробнее описать результат услуги.',
            ])
            ->assertRedirect();

        $this->assertNotification($performer, 'service.rejected');
    }

    public function test_key_event_notification_uses_mail_and_database_channels(): void
    {
        config(['notifications.email_enabled' => true]);
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        foreach (PlatformNotification::EMAIL_EVENT_TYPES as $eventType) {
            $notification = new PlatformNotification($eventType, 'Заголовок', 'Текст уведомления.');

            $this->assertSame(['database', 'mail'], $notification->via($user), $eventType);
        }
    }

    public function test_non_key_event_notification_uses_database_channel_only(): void
    {
        config(['notifications.email_enabled' => true]);
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $notification = new PlatformNotification('review.published', 'Заголовок', 'Текст.');

        $this->assertSame(['database'], $notification->via($user));
    }

    public function test_email_channel_is_disabled_by_config(): void
    {
        config(['notifications.email_enabled' => false]);
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $notification = new PlatformNotification('task_offer.created', 'Заголовок', 'Текст.');

        $this->assertSame(['database'], $notification->via($user));
    }

    public function test_mail_message_contains_subject_body_and_action(): void
    {
        config(['notifications.email_enabled' => true]);
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'name' => 'Мария']);

        $notification = new PlatformNotification(
            'task_offer.created',
            'Новый отклик на задание',
            'Исполнитель отправил отклик на задание «Сделать презентацию».',
            '/customer/tasks/1',
        );

        $mail = $notification->toMail($user);

        $this->assertSame('Таскора: Новый отклик на задание', $mail->subject);
        $this->assertSame('Здравствуйте, Мария!', $mail->greeting);
        $this->assertSame('Открыть на Таскоре', $mail->actionText);
        $this->assertStringStartsWith('http', $mail->actionUrl);
        $this->assertContains('Исполнитель отправил отклик на задание «Сделать презентацию».', $mail->introLines);
    }

    public function test_new_task_offer_sends_email_notification_to_customer(): void
    {
        config(['notifications.email_enabled' => true]);
        \Illuminate\Support\Facades\Notification::fake();

        [$customer, $performer, $task] = $this->taskScenario();

        $this->actingAs($performer)
            ->post(route('tasks.offers.store', $task), [
                'message' => 'Готов выполнить задачу внутри платформы.',
                'price' => 5000,
                'delivery_days' => 3,
            ])
            ->assertRedirect();

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $customer,
            PlatformNotification::class,
            fn (PlatformNotification $notification, array $channels): bool => in_array('mail', $channels, true)
                && in_array('database', $channels, true),
        );
    }

    public function test_new_task_offer_creates_notification_for_customer(): void
    {
        [$customer, $performer, $task] = $this->taskScenario();

        $this->actingAs($performer)
            ->post(route('tasks.offers.store', $task), [
                'message' => 'Готов выполнить задачу внутри платформы.',
                'price' => 5000,
                'delivery_days' => 3,
            ])
            ->assertRedirect();

        $this->assertNotification($customer, 'task_offer.created');
    }

    public function test_accepting_task_offer_creates_notification_for_performer(): void
    {
        [$customer, $performer, $task] = $this->taskScenario();
        $offer = TaskOffer::factory()->for($task)->for($performer, 'performer')->create();

        $this->actingAs($customer)
            ->post(route('customer.task-offers.accept', $offer))
            ->assertRedirect();

        $this->assertNotification($performer, 'task_offer.accepted');
    }

    public function test_rejecting_task_offer_creates_notification_for_performer(): void
    {
        [$customer, $performer, $task] = $this->taskScenario();
        $offer = TaskOffer::factory()->for($task)->for($performer, 'performer')->create();

        $this->actingAs($customer)
            ->post(route('customer.task-offers.reject', $offer))
            ->assertRedirect();

        $this->assertNotification($performer, 'task_offer.rejected');
    }

    public function test_creating_order_from_service_creates_notification_for_performer(): void
    {
        [$customer, $service, $package] = $this->serviceOrderScenario();
        $performer = $service->user;

        $this->actingAs($customer)
            ->post(route('services.order.store', $service), ['package_id' => $package->id])
            ->assertRedirect();

        $this->assertNotification($performer, 'order.created');
    }

    public function test_mark_paid_creates_notification_for_performer(): void
    {
        [, $performer, $order] = $this->orderScenario();

        $this->actingAs($order->customer)
            ->post(route('customer.orders.mark-paid', $order))
            ->assertRedirect();

        $this->assertNotification($performer, 'order.payment_held');
    }

    public function test_submit_work_creates_notification_for_customer(): void
    {
        [$customer, $performer, $order] = $this->orderScenario([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $this->actingAs($performer)
            ->post(route('performer.orders.submit-work', $order), [
                'message' => 'Работа выполнена и готова к проверке.',
            ])
            ->assertRedirect();

        $this->assertNotification($customer, 'order.work_submitted');
    }

    public function test_request_revision_creates_notification_for_performer(): void
    {
        [, $performer, $order] = $this->orderScenario([
            'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_HELD,
        ]);
        $order->submissions()->create([
            'user_id' => $performer->id,
            'message' => 'Сдача работы.',
            'status' => 'submitted',
        ]);

        $this->actingAs($order->customer)
            ->post(route('customer.orders.request-revision', $order), [
                'revision_comment' => 'Please improve the submitted result and attach updated files.',
            ])
            ->assertRedirect();

        $this->assertNotification($performer, 'order.revision_requested');
    }

    public function test_complete_creates_notification_for_performer_and_customer(): void
    {
        [$customer, $performer, $order] = $this->orderScenario([
            'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.complete', $order))
            ->assertRedirect();

        $this->assertNotification($customer, 'order.completed');
        $this->assertNotification($performer, 'order.completed');
    }

    public function test_auto_release_creates_notification_for_performer_and_customer(): void
    {
        [$customer, $performer, $order] = $this->orderScenario([
            'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
            'payment_status' => Order::PAYMENT_HELD,
            'review_hold_until' => now()->subMinute(),
            'auto_release_at' => now()->subMinute(),
        ]);

        Artisan::call('orders:release-due');

        $this->assertNotification($customer, 'order.auto_released');
        $this->assertNotification($performer, 'order.auto_released');
    }

    public function test_new_order_message_notifies_other_participant_only(): void
    {
        [$customer, $performer, $order] = $this->orderScenario([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.messages.store', $order), [
                'body' => 'Обычное сообщение внутри заказа.',
            ])
            ->assertRedirect();

        $this->assertNotification($performer, 'order.message_sent');
        $this->assertNoNotification($customer, 'order.message_sent');
    }

    public function test_new_order_file_notifies_other_participant_only(): void
    {
        Storage::fake('local');
        [$customer, $performer, $order] = $this->orderScenario([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.files.store', $order), [
                'file' => UploadedFile::fake()->create('brief.pdf', 12, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertNotification($performer, 'order.file_uploaded');
        $this->assertNoNotification($customer, 'order.file_uploaded');
    }

    public function test_opening_dispute_notifies_other_order_participant(): void
    {
        [$customer, $performer, $order] = $this->orderScenario([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.disputes.store', $order), $this->disputePayload())
            ->assertRedirect();

        $this->assertNotification($performer, 'dispute.opened');
        $this->assertNoNotification($customer, 'dispute.opened');
    }

    public function test_opening_dispute_notifies_moderators_and_admins(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.orders.disputes.store', $order), $this->disputePayload())
            ->assertRedirect();

        $this->assertNotification($moderator, 'dispute.opened.moderation');
        $this->assertNotification($admin, 'dispute.opened.moderation');
    }

    public function test_taking_dispute_under_review_notifies_customer_and_performer(): void
    {
        [$customer, $performer, $dispute] = $this->disputeScenario();
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);

        $this->actingAs($moderator)
            ->post(route('moderator.disputes.take', $dispute))
            ->assertRedirect();

        $this->assertNotification($customer, 'dispute.under_review');
        $this->assertNotification($performer, 'dispute.under_review');
    }

    public function test_resolving_dispute_notifies_customer_and_performer(): void
    {
        [$customer, $performer, $dispute] = $this->disputeScenario(Dispute::STATUS_UNDER_REVIEW);
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);

        $this->actingAs($moderator)
            ->post(route('moderator.disputes.resolve', $dispute), [
                'resolution' => Dispute::RESOLUTION_RETURN_TO_REVISION,
                'moderator_comment' => 'Вернуть заказ на доработку после проверки материалов.',
            ])
            ->assertRedirect();

        $this->assertNotification($customer, 'dispute.resolved');
        $this->assertNotification($performer, 'dispute.resolved');
    }

    public function test_new_dispute_message_notifies_other_participants_only(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        [$customer, $performer, $dispute] = $this->disputeScenario();

        $this->actingAs($customer)
            ->post(route('customer.disputes.messages.store', $dispute), [
                'body' => 'Добавил детали по спору для проверки.',
            ])
            ->assertRedirect();

        $this->assertNotification($performer, 'dispute.message_sent');
        $this->assertNotification($moderator, 'dispute.message_sent');
        $this->assertNoNotification($customer, 'dispute.message_sent');
    }

    public function test_notifications_contain_correct_url(): void
    {
        [, $performer, $order] = $this->orderScenario();

        $this->actingAs($order->customer)
            ->post(route('customer.orders.mark-paid', $order))
            ->assertRedirect();

        $notification = $this->assertNotification($performer, 'order.payment_held');

        $this->assertStringContainsString("/performer/orders/{$order->id}", $notification->data['url']);
    }

    public function test_notification_service_does_not_duplicate_same_user_in_one_event(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        app(NotificationService::class)->notifyUsers(
            [$user, $user],
            'test.duplicate',
            'Проверка дублей',
            'Одно событие не должно создать два одинаковых уведомления.',
        );

        $this->assertSame(1, $this->notificationsOfType($user, 'test.duplicate')->count());
    }

    private function notify(User $user, string $type): DatabaseNotification
    {
        app(NotificationService::class)->notifyUser(
            $user,
            $type,
            'Тестовое уведомление',
            'Текст тестового уведомления.',
            '/notifications',
        );

        return $user->notifications()->latest()->firstOrFail();
    }

    private function assertNotification(User $user, string $type): DatabaseNotification
    {
        $notification = $this->notificationsOfType($user, $type)->first();

        $this->assertInstanceOf(DatabaseNotification::class, $notification, "Notification {$type} was not created.");

        return $notification;
    }

    private function assertNoNotification(User $user, string $type): void
    {
        $this->assertSame(0, $this->notificationsOfType($user, $type)->count(), "Notification {$type} should not exist.");
    }

    private function notificationsOfType(User $user, string $type)
    {
        return $user->notifications()
            ->get()
            ->filter(fn (DatabaseNotification $notification): bool => ($notification->data['type'] ?? null) === $type)
            ->values();
    }

    /**
     * @return array{0: User, 1: User, 2: Service}
     */
    private function pendingServiceScenario(): array
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $service = Service::factory()
            ->for($performer, 'user')
            ->create(['status' => Service::STATUS_PENDING_REVIEW]);

        return [$moderator, $performer, $service];
    }

    /**
     * @return array{0: User, 1: User, 2: Task}
     */
    private function taskScenario(): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $task = Task::factory()->for($customer, 'customer')->create();

        return [$customer, $performer, $task];
    }

    /**
     * @return array{0: User, 1: Service, 2: ServicePackage}
     */
    private function serviceOrderScenario(): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $service = Service::factory()
            ->for($performer, 'user')
            ->create(['status' => Service::STATUS_PUBLISHED]);
        $package = ServicePackage::factory()->for($service)->create();

        return [$customer, $service, $package];
    }

    /**
     * @param  array<string, mixed>  $orderOverrides
     * @return array{0: User, 1: User, 2: Order}
     */
    private function orderScenario(array $orderOverrides = []): array
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $order = Order::factory()
            ->for($customer, 'customer')
            ->for($performer, 'performer')
            ->create($orderOverrides);

        return [$customer, $performer, $order];
    }

    /**
     * @return array{0: User, 1: User, 2: Dispute}
     */
    private function disputeScenario(string $status = Dispute::STATUS_OPEN): array
    {
        [$customer, $performer, $order] = $this->orderScenario([
            'status' => Order::STATUS_DISPUTED,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        $dispute = Dispute::factory()
            ->for($order)
            ->for($customer, 'openedBy')
            ->create([
                'status' => $status,
                'previous_order_status' => Order::STATUS_IN_PROGRESS,
                'previous_payment_status' => Order::PAYMENT_HELD,
            ]);

        return [$customer, $performer, $dispute];
    }

    /**
     * @return array<string, string>
     */
    private function disputePayload(): array
    {
        return [
            'reason' => Dispute::REASON_POOR_QUALITY,
            'description' => 'Описание проблемы по заказу для проверки спора.',
        ];
    }
}
