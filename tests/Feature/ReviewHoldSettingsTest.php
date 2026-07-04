<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewHoldSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_set_review_hold_days_on_task(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $category = Category::factory()->create();

        $this->actingAs($customer)
            ->post(route('customer.tasks.store'), [
                'category_id' => $category->id,
                'title' => 'Задание со сроком проверки',
                'description' => 'Описание задания для проверки сроков заморозки.',
                'review_hold_days' => 20,
                'publish' => true,
            ])
            ->assertRedirect();

        $this->assertSame(20, Task::firstOrFail()->review_hold_days);
    }

    public function test_review_hold_days_outside_bounds_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $category = Category::factory()->create();

        foreach ([Order::REVIEW_HOLD_MIN_DAYS - 1, Order::REVIEW_HOLD_MAX_DAYS + 1] as $days) {
            $this->actingAs($customer)
                ->post(route('customer.tasks.store'), [
                    'category_id' => $category->id,
                    'title' => 'Задание с неверным сроком',
                    'description' => 'Описание задания с неверным сроком проверки.',
                    'review_hold_days' => $days,
                ])
                ->assertSessionHasErrors('review_hold_days');
        }

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_order_from_offer_inherits_task_review_hold_days(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $task = Task::factory()->for($customer, 'customer')->create([
            'status' => Task::STATUS_PUBLISHED,
            'review_hold_days' => 20,
        ]);
        $offer = TaskOffer::factory()->for($task)->for($performer, 'performer')->create(['status' => TaskOffer::STATUS_SUBMITTED]);

        $this->actingAs($customer)->post(route('customer.task-offers.accept', $offer));

        $this->assertSame(20, Order::firstOrFail()->review_hold_days);
    }

    public function test_task_board_card_shows_review_hold_days(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        Task::factory()->for($customer, 'customer')->create([
            'status' => Task::STATUS_PUBLISHED,
            'review_hold_days' => 5,
        ]);

        $payload = collect($this->get('/tasks')->assertOk()->inertiaProps('tasks'))->first();

        $this->assertSame(5, $payload['review_hold_days']);
    }

    public function test_service_order_respects_customer_choice_and_service_maximum(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $service = Service::factory()->for($performer, 'user')->create([
            'status' => Service::STATUS_PUBLISHED,
            'max_review_hold_days' => 10,
        ]);
        $package = ServicePackage::factory()->for($service)->create(['price' => 3000]);

        // Выбор в пределах максимума услуги.
        $this->actingAs($customer)
            ->post(route('services.order.store', $service), ['package_id' => $package->id, 'review_hold_days' => 5])
            ->assertRedirect();

        $this->assertSame(5, Order::firstOrFail()->review_hold_days);

        // Выше максимума услуги — отклоняется.
        $this->actingAs($customer)
            ->post(route('services.order.store', $service), ['package_id' => $package->id, 'review_hold_days' => 20])
            ->assertSessionHasErrors('review_hold_days');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_performer_can_set_max_review_hold_days_on_service(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();

        $this->actingAs($performer)
            ->post(route('performer.services.store'), [
                'category_id' => $category->id,
                'title' => 'Услуга с ограничением проверки',
                'short_description' => 'Короткое описание услуги.',
                'description' => 'Полное описание услуги.',
                'price_from' => 1500,
                'delivery_days' => 3,
                'max_review_hold_days' => 10,
                'packages' => [
                    ['name' => 'Базовый', 'description' => 'Пакет.', 'price' => 1500, 'delivery_days' => 3, 'revisions_count' => 1],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(10, Service::firstOrFail()->max_review_hold_days);
    }
}
