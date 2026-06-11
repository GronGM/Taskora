<?php

namespace Tests\Feature;

use App\Models\BetaFeedback;
use App\Models\Dispute;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\PaymentOperation;
use App\Models\Service;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_orders_page(): void
    {
        $order = $this->order(['title' => 'Visible order']);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.index'))
            ->assertOk();

        $this->assertSame('Admin/Orders/Index', $response->inertiaPage()['component']);
        $this->assertTrue(collect($response->inertiaProps('orders.data'))->pluck('id')->contains($order->id));
        $this->assertSame(1, $response->inertiaProps('summary.filtered'));
    }

    public function test_admin_orders_page_contains_compact_filter_controls(): void
    {
        $this->order();

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.index'))
            ->assertOk();

        $source = file_get_contents(resource_path('js/Pages/Admin/Orders/Index.jsx'));

        $this->assertSame('Admin/Orders/Index', $response->inertiaPage()['component']);
        $this->assertStringContainsString('Фильтры', $source);
        $this->assertStringContainsString('Показать фильтры', $source);
        $this->assertStringContainsString('Скрыть фильтры', $source);
        $this->assertStringContainsString('aria-expanded', $source);
        $this->assertStringContainsString('activeFilterCount', $source);
    }

    public function test_admin_orders_page_keeps_reset_filters_control_when_filters_are_active(): void
    {
        $this->order(['status' => Order::STATUS_COMPLETED]);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.index', ['status' => Order::STATUS_COMPLETED]))
            ->assertOk();

        $source = file_get_contents(resource_path('js/Pages/Admin/Orders/Index.jsx'));

        $this->assertSame(Order::STATUS_COMPLETED, $response->inertiaProps('filters.status'));
        $this->assertStringContainsString('Сбросить фильтры', $source);
    }

    public function test_guest_is_redirected_from_orders_page(): void
    {
        $this->get(route('admin.orders.index'))->assertRedirect('/login');
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admins_are_forbidden_from_orders_page(string $role): void
    {
        $this->actingAs($this->user($role))
            ->get(route('admin.orders.index'))
            ->assertForbidden();
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admins_are_forbidden_from_order_detail_pages(string $role): void
    {
        $order = $this->order();

        $this->actingAs($this->user($role))
            ->get(route('admin.orders.show', $order))
            ->assertForbidden();

        $this->actingAs($this->user($role))
            ->get(route('admin.orders.events', $order))
            ->assertForbidden();

        $this->actingAs($this->user($role))
            ->get(route('admin.orders.ledger', $order))
            ->assertForbidden();
    }

    public function test_admin_can_filter_orders_by_status(): void
    {
        $matched = $this->order(['status' => Order::STATUS_COMPLETED, 'title' => 'Completed order']);
        $missed = $this->order(['status' => Order::STATUS_IN_PROGRESS, 'title' => 'In progress order']);

        $ids = $this->indexIds(['status' => Order::STATUS_COMPLETED]);

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($missed->id));
    }

    public function test_admin_can_filter_orders_by_payment_status(): void
    {
        $matched = $this->order(['payment_status' => Order::PAYMENT_HELD]);
        $missed = $this->order(['payment_status' => Order::PAYMENT_UNPAID]);

        $ids = $this->indexIds(['payment_status' => Order::PAYMENT_HELD]);

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($missed->id));
    }

    public function test_admin_can_filter_orders_by_source_type(): void
    {
        $matched = $this->order(['source_type' => Order::SOURCE_TASK_OFFER]);
        $missed = $this->order(['source_type' => Order::SOURCE_SERVICE]);

        $ids = $this->indexIds(['source_type' => Order::SOURCE_TASK_OFFER]);

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($missed->id));
    }

    public function test_admin_can_search_orders_by_title(): void
    {
        $matched = $this->order(['title' => 'Unique admin search title']);
        $missed = $this->order(['title' => 'Another order']);

        $ids = $this->indexIds(['q' => 'Unique admin search']);

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($missed->id));
    }

    public function test_admin_can_search_orders_by_id(): void
    {
        $matched = $this->order(['title' => 'Search by id']);
        $missedCustomer = $this->user(User::ROLE_CUSTOMER, ['email' => "missed-{$matched->id}@taskora.local"]);
        $missed = $this->order([
            'customer_id' => $missedCustomer->id,
            'title' => "Other id {$matched->id}",
        ]);

        $ids = $this->indexIds(['q' => (string) $matched->id]);

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($missed->id));
    }

    public function test_admin_can_search_orders_by_customer_email(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER, ['email' => 'search-customer@taskora.local']);
        $matched = $this->order(['customer_id' => $customer->id]);
        $missed = $this->order();

        $ids = $this->indexIds(['q' => 'search-customer']);

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($missed->id));
    }

    public function test_admin_can_search_orders_by_performer_email(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER, ['email' => 'search-performer@taskora.local']);
        $matched = $this->order(['performer_id' => $performer->id]);
        $missed = $this->order();

        $ids = $this->indexIds(['q' => 'search-performer']);

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($missed->id));
    }

    public function test_admin_can_filter_orders_with_disputes(): void
    {
        $matched = $this->order();
        $missed = $this->order();
        Dispute::factory()->for($matched)->create(['opened_by' => $matched->customer_id]);

        $ids = $this->indexIds(['has_dispute' => 'yes']);

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($missed->id));
    }

    public function test_admin_can_filter_orders_without_disputes(): void
    {
        $withDispute = $this->order();
        $withoutDispute = $this->order();
        Dispute::factory()->for($withDispute)->create(['opened_by' => $withDispute->customer_id]);

        $ids = $this->indexIds(['has_dispute' => 'no']);

        $this->assertFalse($ids->contains($withDispute->id));
        $this->assertTrue($ids->contains($withoutDispute->id));
    }

    public function test_admin_can_filter_orders_by_created_date_range(): void
    {
        $matched = $this->order(['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)]);
        $missed = $this->order(['created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)]);

        $ids = $this->indexIds([
            'date_from' => now()->subDays(3)->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($missed->id));
    }

    public function test_admin_can_filter_orders_by_price_range(): void
    {
        $matched = $this->pricedOrder(5000);
        $tooCheap = $this->pricedOrder(1000);
        $tooExpensive = $this->pricedOrder(15000);

        $ids = $this->indexIds(['price_min' => '3000', 'price_max' => '8000']);

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($tooCheap->id));
        $this->assertFalse($ids->contains($tooExpensive->id));
    }

    public function test_admin_can_filter_orders_by_customer_id_and_performer_id(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER);
        $performer = $this->user(User::ROLE_PERFORMER);
        $matched = $this->order([
            'customer_id' => $customer->id,
            'performer_id' => $performer->id,
        ]);
        $missed = $this->order();

        $ids = $this->indexIds([
            'customer_id' => (string) $customer->id,
            'performer_id' => (string) $performer->id,
        ]);

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($missed->id));
    }

    public function test_admin_can_sort_orders_by_price_high(): void
    {
        $cheap = $this->pricedOrder(1000);
        $expensive = $this->pricedOrder(9000);

        $ids = $this->indexIds(['sort' => 'price_high']);

        $this->assertSame($expensive->id, $ids->first());
        $this->assertTrue($ids->contains($cheap->id));
    }

    public function test_admin_can_sort_orders_by_oldest(): void
    {
        $old = $this->order(['created_at' => now()->subDays(10), 'updated_at' => now()->subDays(10)]);
        $new = $this->order(['created_at' => now(), 'updated_at' => now()]);

        $ids = $this->indexIds(['sort' => 'oldest']);

        $this->assertSame($old->id, $ids->first());
        $this->assertTrue($ids->contains($new->id));
    }

    public function test_orders_index_paginates_and_preserves_filters(): void
    {
        Order::factory()->count(26)->completed()->create();

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.index', ['status' => Order::STATUS_COMPLETED]))
            ->assertOk();

        $this->assertCount(25, $response->inertiaProps('orders.data'));
        $this->assertSame(26, $response->inertiaProps('orders.total'));
        $this->assertStringContainsString('status=completed', $response->inertiaProps('orders.next_page_url'));
    }

    public function test_orders_index_marks_active_dispute(): void
    {
        $order = $this->order();
        $dispute = Dispute::factory()->for($order)->create(['opened_by' => $order->customer_id]);

        $row = collect($this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->inertiaProps('orders.data'))
            ->firstWhere('id', $order->id);

        $this->assertTrue($row['has_active_dispute']);
        $this->assertSame($dispute->id, $row['active_dispute_id']);
    }

    public function test_admin_sees_order_detail_page(): void
    {
        $order = $this->order(['title' => 'Detail order']);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk();

        $this->assertSame('Admin/Orders/Show', $response->inertiaPage()['component']);
        $this->assertSame($order->id, $response->inertiaProps('order.id'));
        $this->assertSame('Detail order', $response->inertiaProps('order.title'));
    }

    public function test_order_detail_contains_participants_with_admin_links_and_rating(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER, ['email' => 'detail-customer@taskora.local']);
        $performer = $this->user(User::ROLE_PERFORMER, [
            'email' => 'detail-performer@taskora.local',
            'performer_rating' => 4.8,
        ]);
        $order = $this->order(['customer_id' => $customer->id, 'performer_id' => $performer->id]);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk();

        $this->assertSame($customer->email, $response->inertiaProps('order.customer.email'));
        $this->assertSame(route('admin.users.show', $customer), $response->inertiaProps('order.customer.admin_url'));
        $this->assertSame($performer->email, $response->inertiaProps('order.performer.email'));
        $this->assertSame(4.8, $response->inertiaProps('order.performer.performer_rating'));
    }

    public function test_order_detail_contains_service_source_link(): void
    {
        $service = Service::factory()->create(['slug' => 'admin-source-service']);
        $order = $this->order([
            'source_type' => Order::SOURCE_SERVICE,
            'service_id' => $service->id,
        ]);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk();

        $this->assertSame($service->id, $response->inertiaProps('order.source.service.id'));
        $this->assertSame('/services/admin-source-service', $response->inertiaProps('order.source.service.public_url'));
    }

    public function test_order_detail_contains_task_offer_source_link(): void
    {
        $task = Task::factory()->create(['slug' => 'admin-source-task']);
        $offer = TaskOffer::factory()->for($task)->create();
        $order = $this->order([
            'source_type' => Order::SOURCE_TASK_OFFER,
            'task_id' => $task->id,
            'task_offer_id' => $offer->id,
        ]);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk();

        $this->assertSame($task->id, $response->inertiaProps('order.source.task.id'));
        $this->assertSame($offer->id, $response->inertiaProps('order.source.task_offer.id'));
        $this->assertSame('/tasks/admin-source-task', $response->inertiaProps('order.source.task.public_url'));
    }

    public function test_order_detail_contains_workspace_counts_messages_and_file_metadata_only(): void
    {
        $order = $this->order();
        OrderMessage::factory()->for($order)->create([
            'user_id' => $order->customer_id,
            'body' => 'Visible workspace message',
        ]);
        OrderFile::factory()->for($order)->create([
            'user_id' => $order->performer_id,
            'original_name' => 'public-name.txt',
            'stored_name' => 'stored-secret.txt',
            'path' => 'orders/secret/stored-secret.txt',
            'disk' => 'local',
        ]);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk();

        $file = data_get($response->inertiaProps('workspace.files'), 0);

        $this->assertSame(1, $response->inertiaProps('workspace.messages_count'));
        $this->assertSame('Visible workspace message', $response->inertiaProps('workspace.messages.0.body'));
        $this->assertSame('public-name.txt', $file['original_name']);
        $this->assertArrayNotHasKey('path', $file);
        $this->assertArrayNotHasKey('stored_name', $file);
        $this->assertArrayNotHasKey('disk', $file);
        $this->assertStringNotContainsString('orders/secret/stored-secret.txt', $response->getContent());
        $this->assertStringNotContainsString('stored-secret.txt', $response->getContent());
    }

    public function test_order_detail_contains_latest_events(): void
    {
        $order = $this->order();
        OrderEvent::factory()->for($order)->create([
            'type' => OrderEvent::TYPE_ORDER_CREATED,
            'payload' => ['note' => 'old event'],
            'created_at' => now()->subDay(),
        ]);
        $newEvent = OrderEvent::factory()->for($order)->create([
            'type' => OrderEvent::TYPE_MESSAGE_SENT,
            'payload' => ['note' => 'latest event'],
            'created_at' => now(),
        ]);

        $events = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->inertiaProps('events');

        $this->assertSame($newEvent->id, data_get($events, '0.id'));
        $this->assertStringContainsString('latest event', data_get($events, '0.summary'));
    }

    public function test_order_detail_contains_finance_summary(): void
    {
        [$order, $operation, $ledgerEntry] = $this->orderWithFinance();

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk();

        $this->assertSame(1, $response->inertiaProps('finance.operations_count'));
        $this->assertSame($operation->id, $response->inertiaProps('finance.operations.0.id'));
        $this->assertSame($ledgerEntry->id, $response->inertiaProps('finance.ledger_entries.0.id'));
        $this->assertSame(LedgerEntry::ACCOUNT_ESCROW, $response->inertiaProps('finance.account_summary.0.account'));
    }

    public function test_order_detail_contains_disputes_with_moderator_links(): void
    {
        $order = $this->order();
        $dispute = Dispute::factory()->for($order)->create(['opened_by' => $order->customer_id]);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk();

        $this->assertSame($dispute->id, $response->inertiaProps('disputes.active.id'));
        $this->assertSame(route('moderator.disputes.show', $dispute), $response->inertiaProps('disputes.items.0.show_url'));
    }

    public function test_order_detail_contains_related_beta_feedback(): void
    {
        $order = $this->order();
        $feedback = BetaFeedback::factory()->create([
            'title' => "Problem with order #{$order->id}",
            'description' => 'Order detail issue',
            'page_url' => "/admin/orders/{$order->id}",
        ]);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk();

        $this->assertSame($feedback->id, $response->inertiaProps('betaFeedback.0.id'));
    }

    public function test_order_detail_does_not_expose_user_password_or_remember_token(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER, ['remember_token' => 'secret-remember-token']);
        $order = $this->order(['customer_id' => $customer->id]);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk();

        $this->assertStringNotContainsString($customer->password, $response->getContent());
        $this->assertStringNotContainsString('secret-remember-token', $response->getContent());
        $this->assertStringNotContainsString('remember_token', $response->getContent());
    }

    public function test_order_detail_does_not_expose_full_ip_or_auth_field_names(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER, ['last_login_ip' => '192.168.10.55']);
        $order = $this->order(['customer_id' => $customer->id]);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk();

        $this->assertStringNotContainsString('192.168.10.55', $response->getContent());
        $this->assertStringNotContainsString('last_login_ip', $response->getContent());
    }

    public function test_admin_sees_order_events_page(): void
    {
        $order = $this->order();
        $event = OrderEvent::factory()->for($order)->create([
            'type' => OrderEvent::TYPE_MESSAGE_SENT,
            'payload' => ['body' => 'event payload'],
        ]);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.events', $order))
            ->assertOk();

        $this->assertSame('Admin/Orders/Events', $response->inertiaPage()['component']);
        $this->assertSame($event->id, $response->inertiaProps('events.data.0.id'));
    }

    public function test_admin_can_filter_order_events_by_type(): void
    {
        $order = $this->order();
        $matched = OrderEvent::factory()->for($order)->create(['type' => OrderEvent::TYPE_FILE_UPLOADED]);
        $missed = OrderEvent::factory()->for($order)->create(['type' => OrderEvent::TYPE_MESSAGE_SENT]);

        $ids = collect($this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.events', [$order, 'type' => OrderEvent::TYPE_FILE_UPLOADED]))
            ->assertOk()
            ->inertiaProps('events.data'))
            ->pluck('id');

        $this->assertTrue($ids->contains($matched->id));
        $this->assertFalse($ids->contains($missed->id));
    }

    public function test_order_event_type_options_do_not_keep_relation_ordering(): void
    {
        $order = $this->order();
        OrderEvent::factory()->for($order)->create(['type' => OrderEvent::TYPE_FILE_UPLOADED]);
        OrderEvent::factory()->for($order)->create(['type' => OrderEvent::TYPE_MESSAGE_SENT]);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            if (str_contains($query->sql, 'select distinct') && str_contains($query->sql, 'order_events')) {
                $queries[] = $query->sql;
            }
        });

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.events', $order))
            ->assertOk();

        $this->assertNotEmpty($queries);
        $this->assertTrue(collect($queries)->every(
            fn (string $sql): bool => ! str_contains(strtolower($sql), 'order by')
        ));
    }

    public function test_admin_can_sort_order_events_oldest_first(): void
    {
        $order = $this->order();
        $old = OrderEvent::factory()->for($order)->create(['created_at' => now()->subDays(2)]);
        $new = OrderEvent::factory()->for($order)->create(['created_at' => now()]);

        $ids = collect($this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.events', [$order, 'sort' => 'oldest']))
            ->assertOk()
            ->inertiaProps('events.data'))
            ->pluck('id');

        $this->assertSame($old->id, $ids->first());
        $this->assertTrue($ids->contains($new->id));
    }

    public function test_admin_sees_order_ledger_page(): void
    {
        [$order, $operation, $ledgerEntry] = $this->orderWithFinance();

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.ledger', $order))
            ->assertOk();

        $this->assertSame('Admin/Orders/Ledger', $response->inertiaPage()['component']);
        $this->assertSame($operation->id, $response->inertiaProps('operations.data.0.id'));
        $this->assertSame($ledgerEntry->id, $response->inertiaProps('ledgerEntries.data.0.id'));
        $this->assertSame(5000, $response->inertiaProps('accountSummary.0.amount'));
    }

    public function test_admin_orders_are_read_only_routes(): void
    {
        $order = $this->order();
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->patch(route('admin.orders.show', $order), ['status' => Order::STATUS_CANCELED])
            ->assertMethodNotAllowed();

        $this->actingAs($admin)
            ->delete(route('admin.orders.show', $order))
            ->assertMethodNotAllowed();

        $this->assertNotSame(Order::STATUS_CANCELED, $order->refresh()->status);
    }

    public function test_admin_dashboard_links_to_orders(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin/dashboard')
            ->assertOk();

        $this->assertStringContainsString('/admin/orders', file_get_contents(resource_path('js/Pages/Dashboards/Admin.jsx')));
    }

    public function test_admin_order_react_pages_do_not_reference_private_file_paths_or_tables(): void
    {
        $source = collect([
            resource_path('js/Pages/Admin/Orders/Index.jsx'),
            resource_path('js/Pages/Admin/Orders/Show.jsx'),
            resource_path('js/Pages/Admin/Orders/Events.jsx'),
            resource_path('js/Pages/Admin/Orders/Ledger.jsx'),
        ])->map(fn (string $path): string => file_get_contents($path))->implode("\n");

        $this->assertStringNotContainsString('stored_name', $source);
        $this->assertStringNotContainsString('download_url', $source);
        $this->assertStringNotContainsString('<table', $source);
    }

    public function test_admin_order_react_pages_use_russian_finance_and_event_labels(): void
    {
        $order = $this->order();

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Orders/Show'));

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.events', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Orders/Events'));

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.ledger', $order))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Orders/Ledger'));

        $source = collect([
            resource_path('js/Pages/Admin/Orders/Show.jsx'),
            resource_path('js/Pages/Admin/Orders/Events.jsx'),
            resource_path('js/Pages/Admin/Orders/Ledger.jsx'),
        ])->map(fn (string $path): string => file_get_contents($path))->implode("\n");

        $this->assertStringContainsString('Рабочая область', $source);
        $this->assertStringContainsString('События заказа', $source);
        $this->assertStringContainsString('Финансовый журнал', $source);
        $this->assertStringContainsString('Платежные операции', $source);
        $this->assertStringContainsString('Записи журнала', $source);
        $this->assertStringNotContainsString('Admin finance', $source);
        $this->assertStringNotContainsString('Ledger entries', $source);
        $this->assertStringNotContainsString('Payment operations', $source);
        $this->assertStringNotContainsString('Order events', $source);
    }

    public static function nonAdminRoles(): array
    {
        return [
            'moderator' => [User::ROLE_MODERATOR],
            'customer' => [User::ROLE_CUSTOMER],
            'performer' => [User::ROLE_PERFORMER],
        ];
    }

    /**
     * @param  array<string, string>  $query
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function indexIds(array $query): \Illuminate\Support\Collection
    {
        return collect($this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.orders.index', $query))
            ->assertOk()
            ->inertiaProps('orders.data'))
            ->pluck('id');
    }

    private function user(string $role, array $overrides = []): User
    {
        return User::factory()->create([
            'role' => $role,
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function order(array $overrides = []): Order
    {
        return Order::factory()->create($overrides);
    }

    private function pricedOrder(int $price): Order
    {
        $feeAmount = (int) round($price * 15 / 100);

        return $this->order([
            'price' => $price,
            'platform_fee_amount' => $feeAmount,
            'performer_amount' => $price - $feeAmount,
        ]);
    }

    /**
     * @return array{0: Order, 1: PaymentOperation, 2: LedgerEntry}
     */
    private function orderWithFinance(): array
    {
        $order = $this->pricedOrder(5000);
        $operation = PaymentOperation::factory()->create([
            'order_id' => $order->id,
            'user_id' => $order->customer_id,
            'amount' => 5000,
        ]);
        $ledgerEntry = LedgerEntry::factory()->create([
            'order_id' => $order->id,
            'payment_operation_id' => $operation->id,
            'user_id' => $order->performer_id,
            'account' => LedgerEntry::ACCOUNT_ESCROW,
            'direction' => LedgerEntry::DIRECTION_CREDIT,
            'amount' => 5000,
        ]);

        return [$order, $operation, $ledgerEntry];
    }
}
