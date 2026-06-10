<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ModerationFlag;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTaskOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sees_customer_tasks(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $response = $this->actingAs($customer)->get('/customer/tasks')->assertOk();

        $this->assertSame('Customer/Tasks/Index', $response->inertiaPage()['component']);
    }

    public function test_performer_cannot_open_customer_tasks(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        $this->actingAs($performer)
            ->get('/customer/tasks')
            ->assertForbidden();
    }

    public function test_customer_can_create_draft_task(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $category = Category::factory()->create();

        $this->actingAs($customer)
            ->post('/customer/tasks', $this->validTaskPayload($category))
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'user_id' => $customer->id,
            'title' => 'Подготовить аккуратное тестовое задание',
            'status' => Task::STATUS_DRAFT,
        ]);
    }

    public function test_customer_can_publish_task(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $category = Category::factory()->create();

        $this->actingAs($customer)
            ->post('/customer/tasks', [
                ...$this->validTaskPayload($category),
                'publish' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'user_id' => $customer->id,
            'title' => 'Подготовить аккуратное тестовое задание',
            'status' => Task::STATUS_PUBLISHED,
        ]);
    }

    public function test_published_task_visible_on_task_board(): void
    {
        $task = Task::factory()->create([
            'title' => 'Публичное тестовое задание',
            'status' => Task::STATUS_PUBLISHED,
        ]);

        $response = $this->get('/tasks')->assertOk();

        $this->assertTrue(
            collect($response->inertiaProps('tasks'))->pluck('title')->contains($task->title),
        );
    }

    public function test_draft_task_not_visible_on_task_board(): void
    {
        $task = Task::factory()->draft()->create([
            'title' => 'Скрытый черновик задания',
        ]);

        $response = $this->get('/tasks')->assertOk();

        $this->assertFalse(
            collect($response->inertiaProps('tasks'))->pluck('title')->contains($task->title),
        );
    }

    public function test_archived_task_not_visible_on_task_board(): void
    {
        $task = Task::factory()->archived()->create([
            'title' => 'Архивное задание',
        ]);

        $response = $this->get('/tasks')->assertOk();

        $this->assertFalse(
            collect($response->inertiaProps('tasks'))->pluck('title')->contains($task->title),
        );
    }

    public function test_guest_sees_public_task_board(): void
    {
        Task::factory()->create();

        $response = $this->get('/tasks')->assertOk();

        $this->assertSame('Tasks/Index', $response->inertiaPage()['component']);
    }

    public function test_guest_sees_published_task_page(): void
    {
        $task = Task::factory()->create([
            'title' => 'Открытое задание для гостя',
            'slug' => 'otkrytoe-zadanie-dlya-gostya',
            'status' => Task::STATUS_PUBLISHED,
        ]);

        $response = $this->get("/tasks/{$task->slug}")->assertOk();

        $this->assertSame('Tasks/Show', $response->inertiaPage()['component']);
        $this->assertSame('Открытое задание для гостя', $response->inertiaProps('task.title'));
    }

    public function test_guest_does_not_see_draft_task_directly(): void
    {
        $task = Task::factory()->draft()->create([
            'slug' => 'skrytyy-chernovik-zadaniya',
        ]);

        $this->get("/tasks/{$task->slug}")
            ->assertNotFound();
    }

    public function test_performer_can_offer_on_published_task(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $task = Task::factory()->create(['status' => Task::STATUS_PUBLISHED]);

        $this->actingAs($performer)
            ->post(route('tasks.offers.store', $task), $this->validOfferPayload())
            ->assertRedirect(route('performer.offers.index'));

        $this->assertDatabaseHas('task_offers', [
            'task_id' => $task->id,
            'user_id' => $performer->id,
            'status' => TaskOffer::STATUS_SUBMITTED,
        ]);
        $this->assertSame(1, $task->refresh()->offers_count);
    }

    public function test_performer_cannot_offer_twice(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $task = Task::factory()->create(['status' => Task::STATUS_PUBLISHED]);
        TaskOffer::factory()->for($task)->for($performer, 'performer')->create();

        $this->actingAs($performer)
            ->post(route('tasks.offers.store', $task), $this->validOfferPayload())
            ->assertForbidden();

        $this->assertSame(1, $task->offers()->count());
    }

    public function test_customer_cannot_offer_on_task(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $task = Task::factory()->create(['status' => Task::STATUS_PUBLISHED]);

        $this->actingAs($customer)
            ->post(route('tasks.offers.store', $task), $this->validOfferPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('task_offers', 0);
    }

    public function test_performer_can_withdraw_own_offer(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $task = Task::factory()->create(['offers_count' => 1]);
        $offer = TaskOffer::factory()->for($task)->for($performer, 'performer')->create();

        $this->actingAs($performer)
            ->post(route('performer.task-offers.withdraw', $offer))
            ->assertRedirect(route('performer.offers.index'));

        $this->assertSame(TaskOffer::STATUS_WITHDRAWN, $offer->refresh()->status);
        $this->assertSame(0, $task->refresh()->offers_count);
    }

    public function test_performer_cannot_withdraw_foreign_offer(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $otherPerformer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $task = Task::factory()->create();
        $offer = TaskOffer::factory()->for($task)->for($otherPerformer, 'performer')->create();

        $this->actingAs($performer)
            ->post(route('performer.task-offers.withdraw', $offer))
            ->assertForbidden();

        $this->assertSame(TaskOffer::STATUS_SUBMITTED, $offer->refresh()->status);
    }

    public function test_customer_sees_offers_on_own_task(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $task = Task::factory()->for($customer, 'customer')->create();
        TaskOffer::factory()->for($task)->for($performer, 'performer')->create([
            'message' => 'Готов выполнить задачу внутри платформы.',
        ]);

        $response = $this->actingAs($customer)
            ->get(route('customer.tasks.show', $task))
            ->assertOk();

        $this->assertSame('Customer/Tasks/Show', $response->inertiaPage()['component']);
        $this->assertSame('Готов выполнить задачу внутри платформы.', $response->inertiaProps('task.offers.0.message'));
    }

    public function test_customer_does_not_see_offers_on_foreign_task(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $otherCustomer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $task = Task::factory()->for($otherCustomer, 'customer')->create();

        $this->actingAs($customer)
            ->get(route('customer.tasks.show', $task))
            ->assertForbidden();
    }

    public function test_task_with_email_phone_or_telegram_is_not_saved(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $category = Category::factory()->create();

        foreach ([
            'Напишите на test@example.com для деталей.',
            'Созвонимся по +7 999 123-45-67 перед началом.',
            'Детали отправлю в тг @taskora_helper.',
        ] as $description) {
            $this->actingAs($customer)
                ->from('/customer/tasks/create')
                ->post('/customer/tasks', [
                    ...$this->validTaskPayload($category),
                    'description' => $description,
                ])
                ->assertRedirect('/customer/tasks/create')
                ->assertSessionHasErrors('description');
        }

        $this->assertDatabaseMissing('tasks', [
            'title' => 'Подготовить аккуратное тестовое задание',
        ]);
        $this->assertSame(3, ModerationFlag::where('entity_type', Task::class)->count());
    }

    public function test_offer_with_email_phone_or_telegram_is_not_saved(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $task = Task::factory()->create(['status' => Task::STATUS_PUBLISHED]);

        foreach ([
            'Пишите на test@example.com, обсудим.',
            'Мой номер +7 999 123-45-67 для деталей.',
            'Готов обсудить в телеграм @taskora_helper.',
        ] as $message) {
            $this->actingAs($performer)
                ->from($task->url)
                ->post(route('tasks.offers.store', $task), [
                    ...$this->validOfferPayload(),
                    'message' => $message,
                ])
                ->assertRedirect($task->url)
                ->assertSessionHasErrors('message');
        }

        $this->assertDatabaseCount('task_offers', 0);
        $this->assertSame(3, ModerationFlag::where('entity_type', TaskOffer::class)->count());
    }

    public function test_contact_violation_creates_moderation_flag(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $category = Category::factory()->create();

        $this->actingAs($customer)
            ->from('/customer/tasks/create')
            ->post('/customer/tasks', [
                ...$this->validTaskPayload($category),
                'description' => 'Можно написать на test@example.com перед началом.',
            ])
            ->assertSessionHasErrors('description');

        $this->assertDatabaseHas('moderation_flags', [
            'user_id' => $customer->id,
            'entity_type' => Task::class,
            'entity_id' => null,
            'reason' => 'contact_detected_in_task',
            'matched_type' => 'email',
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validTaskPayload(Category $category): array
    {
        return [
            'category_id' => $category->id,
            'title' => 'Подготовить аккуратное тестовое задание',
            'description' => 'Нужно подготовить результат внутри платформы: описать структуру, согласовать формат и передать итоговые материалы.',
            'budget_min' => 2000,
            'budget_max' => 4000,
            'deadline_at' => now()->addDays(7)->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validOfferPayload(): array
    {
        return [
            'message' => 'Готов выполнить задачу внутри платформы, уточню детали по описанию и передам результат в срок.',
            'price' => 3000,
            'delivery_days' => 4,
        ];
    }
}
