<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_403_page_displays_without_technical_details(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
            ->get('/performer/dashboard')
            ->assertForbidden()
            ->assertInertia(fn ($page) => $page
                ->component('Error')
                ->where('status', 403)
            );
    }

    public function test_404_page_displays_without_technical_details(): void
    {
        $this->get('/missing-production-page')
            ->assertNotFound()
            ->assertInertia(fn ($page) => $page
                ->component('Error')
                ->where('status', 404)
            );
    }

    public function test_guest_cannot_open_private_dashboard_pages(): void
    {
        $this->get('/customer/dashboard')->assertRedirect('/login');
        $this->get('/performer/dashboard')->assertRedirect('/login');
        $this->get('/moderator/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_foreign_user_cannot_download_private_order_file(): void
    {
        Storage::fake('local');

        $foreignCustomer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        [, , $order] = $this->orderScenario();
        $file = $this->storedOrderFile($order);

        $this->actingAs($foreignCustomer)
            ->get(route('customer.orders.files.download', [$order, $file]))
            ->assertForbidden();
    }

    public function test_order_participant_can_download_private_order_file(): void
    {
        Storage::fake('local');

        [$customer, , $order] = $this->orderScenario();
        $file = $this->storedOrderFile($order, $customer);

        $this->actingAs($customer)
            ->get(route('customer.orders.files.download', [$order, $file]))
            ->assertOk();
    }

    public function test_private_order_file_is_not_available_through_public_storage_url(): void
    {
        Storage::fake('local');

        [, , $order] = $this->orderScenario();
        $file = $this->storedOrderFile($order);

        $this->get('/storage/'.$file->path)->assertForbidden();
    }

    public function test_order_message_rate_limit_blocks_frequent_messages(): void
    {
        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->actingAs($customer)
                ->post(route('customer.orders.messages.store', $order), [
                    'body' => "Сообщение по заказу {$attempt}",
                ])
                ->assertRedirect();
        }

        $this->actingAs($customer)
            ->post(route('customer.orders.messages.store', $order), [
                'body' => 'Сообщение сверх лимита',
            ])
            ->assertStatus(429);
    }

    public function test_order_file_upload_rate_limit_blocks_frequent_uploads(): void
    {
        Storage::fake('local');

        [$customer, , $order] = $this->orderScenario([
            'status' => Order::STATUS_IN_PROGRESS,
            'payment_status' => Order::PAYMENT_HELD,
        ]);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->actingAs($customer)
                ->post(route('customer.orders.files.store', $order), [
                    'file' => UploadedFile::fake()->create("brief-{$attempt}.pdf", 12, 'application/pdf'),
                ])
                ->assertRedirect();
        }

        $this->actingAs($customer)
            ->post(route('customer.orders.files.store', $order), [
                'file' => UploadedFile::fake()->create('brief-over-limit.pdf', 12, 'application/pdf'),
            ])
            ->assertStatus(429);
    }

    public function test_task_offer_rate_limit_blocks_frequent_offers(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $tasks = Task::factory()
            ->count(31)
            ->for($customer, 'customer')
            ->for($category)
            ->create();

        foreach ($tasks->take(30) as $index => $task) {
            $this->actingAs($performer)
                ->post(route('tasks.offers.store', $task), [
                    'message' => "Готов выполнить задание номер {$index}.",
                    'price' => 5000,
                    'delivery_days' => 5,
                ])
                ->assertRedirect();
        }

        $this->actingAs($performer)
            ->post(route('tasks.offers.store', $tasks->last()), [
                'message' => 'Готов выполнить еще одно задание.',
                'price' => 5000,
                'delivery_days' => 5,
            ])
            ->assertStatus(429);
    }

    public function test_release_due_orders_command_is_registered_in_scheduler(): void
    {
        Artisan::call('schedule:list');

        $this->assertStringContainsString('orders:release-due', Artisan::output());
    }

    public function test_route_list_has_no_debug_login_or_smoke_login_routes(): void
    {
        Artisan::call('route:list', ['--except-vendor' => true]);

        $routes = Artisan::output();

        $this->assertStringNotContainsString('_codex-login', $routes);
        $this->assertStringNotContainsString('smoke-login', $routes);
    }

    public function test_main_public_pages_are_available(): void
    {
        [$category, $service, $task] = $this->publicScenario();

        $this->get('/')->assertOk();
        $this->get('/catalog')->assertOk();
        $this->get(route('catalog.category', $category))->assertOk();
        $this->get(route('services.show', $service))->assertOk();
        $this->get('/tasks')->assertOk();
        $this->get(route('tasks.show', $task))->assertOk();
        $this->get('/performers')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_main_dashboard_pages_are_available_for_roles(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($customer)->get('/customer/dashboard')->assertOk();
        $this->actingAs($performer)->get('/performer/dashboard')->assertOk();
        $this->actingAs($moderator)->get('/moderator/dashboard')->assertOk();
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_wrong_role_gets_403_for_dashboard(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        $this->actingAs($performer)
            ->get('/customer/dashboard')
            ->assertForbidden()
            ->assertInertia(fn ($page) => $page
                ->component('Error')
                ->where('status', 403)
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

    /**
     * @return array{0: Category, 1: Service, 2: Task}
     */
    private function publicScenario(): array
    {
        $category = Category::factory()->create([
            'slug' => 'public-category',
            'is_active' => true,
        ]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $service = Service::factory()
            ->for($performer, 'user')
            ->for($category)
            ->create([
                'slug' => 'public-service',
                'status' => Service::STATUS_PUBLISHED,
            ]);

        ServicePackage::factory()->for($service)->create();

        $task = Task::factory()
            ->for($customer, 'customer')
            ->for($category)
            ->create([
                'slug' => 'public-task',
                'status' => Task::STATUS_PUBLISHED,
            ]);

        return [$category, $service, $task];
    }
}
