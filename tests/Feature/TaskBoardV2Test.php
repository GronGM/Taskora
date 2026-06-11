<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ModerationFlag;
use App\Models\PerformerFavoriteCategory;
use App\Models\PerformerFavoriteTaskType;
use App\Models\Task;
use App\Models\TaskFavorite;
use App\Models\TaskType;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TaskTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskBoardV2Test extends TestCase
{
    use RefreshDatabase;

    public function test_task_type_seeder_creates_required_active_types(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(TaskTypeSeeder::class);

        $this->assertSame(39, TaskType::count());
        $this->assertDatabaseHas('task_types', ['name' => 'Консультация по работе', 'is_active' => true]);
        $this->assertDatabaseHas('task_types', ['name' => 'Word-документ', 'is_active' => true]);
        $this->assertDatabaseHas('task_types', ['name' => 'Лендинг', 'is_active' => true]);
        $this->assertSame(2, TaskType::where('name', 'Инфографика')->count());
    }

    public function test_task_type_relations_are_available(): void
    {
        [$category, $taskType] = $this->categoryWithType();
        $task = $this->publishedTask([
            'category_id' => $category->id,
            'task_type_id' => $taskType->id,
        ]);

        $this->assertTrue($category->taskTypes->contains($taskType));
        $this->assertTrue($taskType->tasks->contains($task));
        $this->assertSame($taskType->id, $task->taskType->id);
    }

    public function test_task_board_includes_task_type_payload(): void
    {
        [$category, $taskType] = $this->categoryWithType();
        $task = $this->publishedTask([
            'category_id' => $category->id,
            'task_type_id' => $taskType->id,
            'title' => 'Задание с видом работы',
        ]);

        $payload = collect($this->get('/tasks')->assertOk()->inertiaProps('tasks'))
            ->firstWhere('id', $task->id);

        $this->assertSame('Задание с видом работы', $payload['title']);
        $this->assertSame($taskType->name, $payload['task_type']['name']);
    }

    public function test_task_board_filters_by_category(): void
    {
        $first = Category::factory()->create(['slug' => 'first-category']);
        $second = Category::factory()->create(['slug' => 'second-category']);

        $this->publishedTask(['category_id' => $first->id, 'title' => 'Подходит по категории']);
        $this->publishedTask(['category_id' => $second->id, 'title' => 'Другая категория']);

        $titles = $this->taskTitles($this->get('/tasks?category=first-category')->assertOk());

        $this->assertTrue($titles->contains('Подходит по категории'));
        $this->assertFalse($titles->contains('Другая категория'));
    }

    public function test_task_board_filters_parent_category_with_children(): void
    {
        $parent = Category::factory()->create(['slug' => 'parent-category']);
        $child = Category::factory()->create(['parent_id' => $parent->id, 'slug' => 'child-category']);

        $this->publishedTask(['category_id' => $child->id, 'title' => 'Задание дочерней категории']);

        $titles = $this->taskTitles($this->get('/tasks?category=parent-category')->assertOk());

        $this->assertTrue($titles->contains('Задание дочерней категории'));
    }

    public function test_task_board_filters_by_task_type(): void
    {
        [$category, $wantedType] = $this->categoryWithType(['slug' => 'wanted-type']);
        $otherType = TaskType::factory()->for($category)->create(['slug' => 'other-type']);

        $this->publishedTask(['category_id' => $category->id, 'task_type_id' => $wantedType->id, 'title' => 'Нужный вид']);
        $this->publishedTask(['category_id' => $category->id, 'task_type_id' => $otherType->id, 'title' => 'Другой вид']);

        $titles = $this->taskTitles($this->get('/tasks?type=wanted-type')->assertOk());

        $this->assertTrue($titles->contains('Нужный вид'));
        $this->assertFalse($titles->contains('Другой вид'));
    }

    public function test_task_board_searches_title_and_description(): void
    {
        $this->publishedTask(['title' => 'Найти презентацию', 'description' => 'Обычное описание']);
        $this->publishedTask(['title' => 'Обычное задание', 'description' => 'Нужно проверить таблицу по плану']);
        $this->publishedTask(['title' => 'Нерелевантная задача', 'description' => 'Без совпадений']);

        $titleSearch = $this->taskTitles($this->get('/tasks?q=презентацию')->assertOk());
        $descriptionSearch = $this->taskTitles($this->get('/tasks?q=таблицу')->assertOk());

        $this->assertTrue($titleSearch->contains('Найти презентацию'));
        $this->assertFalse($titleSearch->contains('Нерелевантная задача'));
        $this->assertTrue($descriptionSearch->contains('Обычное задание'));
    }

    public function test_task_board_filters_by_budget_min(): void
    {
        $this->publishedTask(['title' => 'Низкий бюджет', 'budget_min' => 1000, 'budget_max' => 2000]);
        $this->publishedTask(['title' => 'Высокий бюджет', 'budget_min' => 6000, 'budget_max' => 9000]);

        $titles = $this->taskTitles($this->get('/tasks?budget_min=5000')->assertOk());

        $this->assertFalse($titles->contains('Низкий бюджет'));
        $this->assertTrue($titles->contains('Высокий бюджет'));
    }

    public function test_task_board_filters_by_budget_max(): void
    {
        $this->publishedTask(['title' => 'Доступный бюджет', 'budget_min' => 1000, 'budget_max' => 2500]);
        $this->publishedTask(['title' => 'Дорогой бюджет', 'budget_min' => 6000, 'budget_max' => 9000]);

        $titles = $this->taskTitles($this->get('/tasks?budget_max=3000')->assertOk());

        $this->assertTrue($titles->contains('Доступный бюджет'));
        $this->assertFalse($titles->contains('Дорогой бюджет'));
    }

    public function test_task_board_filters_by_deadline_before(): void
    {
        $this->publishedTask(['title' => 'Близкий срок', 'deadline_at' => now()->addDays(2)]);
        $this->publishedTask(['title' => 'Дальний срок', 'deadline_at' => now()->addDays(12)]);

        $titles = $this->taskTitles($this->get('/tasks?deadline_before='.now()->addDays(3)->toDateString())->assertOk());

        $this->assertTrue($titles->contains('Близкий срок'));
        $this->assertFalse($titles->contains('Дальний срок'));
    }

    public function test_task_board_filters_tasks_without_offers(): void
    {
        $this->publishedTask(['title' => 'Без откликов', 'offers_count' => 0]);
        $this->publishedTask(['title' => 'С откликами', 'offers_count' => 3]);

        $titles = $this->taskTitles($this->get('/tasks?without_offers=1')->assertOk());

        $this->assertTrue($titles->contains('Без откликов'));
        $this->assertFalse($titles->contains('С откликами'));
    }

    public function test_task_board_filters_urgent_tasks(): void
    {
        $this->publishedTask(['title' => 'Срочное задание', 'deadline_at' => now()->addDays(2)]);
        $this->publishedTask(['title' => 'Не срочное задание', 'deadline_at' => now()->addDays(10)]);

        $titles = $this->taskTitles($this->get('/tasks?urgent=1')->assertOk());

        $this->assertTrue($titles->contains('Срочное задание'));
        $this->assertFalse($titles->contains('Не срочное задание'));
    }

    public function test_task_board_sorts_by_budget_high(): void
    {
        $this->publishedTask(['title' => 'Меньше бюджет', 'budget_min' => 1000, 'budget_max' => 2000]);
        $this->publishedTask(['title' => 'Больше бюджет', 'budget_min' => 7000, 'budget_max' => 9000]);

        $this->assertSame('Больше бюджет', $this->taskTitles($this->get('/tasks?sort=budget_high')->assertOk())->first());
    }

    public function test_task_board_sorts_by_budget_low(): void
    {
        $this->publishedTask(['title' => 'Меньше бюджет', 'budget_min' => 1000, 'budget_max' => 2000]);
        $this->publishedTask(['title' => 'Больше бюджет', 'budget_min' => 7000, 'budget_max' => 9000]);

        $this->assertSame('Меньше бюджет', $this->taskTitles($this->get('/tasks?sort=budget_low')->assertOk())->first());
    }

    public function test_task_board_sorts_by_offers_low(): void
    {
        $this->publishedTask(['title' => 'Много откликов', 'offers_count' => 7]);
        $this->publishedTask(['title' => 'Мало откликов', 'offers_count' => 1]);

        $this->assertSame('Мало откликов', $this->taskTitles($this->get('/tasks?sort=offers_low')->assertOk())->first());
    }

    public function test_performer_can_add_category_to_favorites(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $category = Category::factory()->create();

        $this->actingAs($performer)
            ->post(route('categories.favorite.store', $category))
            ->assertRedirect();

        $this->assertDatabaseHas('performer_favorite_categories', [
            'user_id' => $performer->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_category_favorite_is_not_duplicated(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $category = Category::factory()->create();

        $this->actingAs($performer)->post(route('categories.favorite.store', $category));
        $this->actingAs($performer)->post(route('categories.favorite.store', $category));

        $this->assertSame(1, PerformerFavoriteCategory::count());
    }

    public function test_performer_can_remove_category_from_favorites(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $category = Category::factory()->create();
        PerformerFavoriteCategory::create(['user_id' => $performer->id, 'category_id' => $category->id]);

        $this->actingAs($performer)
            ->delete(route('categories.favorite.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseCount('performer_favorite_categories', 0);
    }

    #[DataProvider('nonPerformerRoles')]
    public function test_only_performer_can_favorite_categories(string $role): void
    {
        $user = $this->user($role);
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->post(route('categories.favorite.store', $category))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_category_favorite_route(): void
    {
        $category = Category::factory()->create();

        $this->post(route('categories.favorite.store', $category))
            ->assertRedirect('/login');
    }

    public function test_inactive_category_cannot_be_favorited(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $category = Category::factory()->create(['is_active' => false]);

        $this->actingAs($performer)
            ->post(route('categories.favorite.store', $category))
            ->assertNotFound();
    }

    public function test_performer_can_add_task_type_to_favorites(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        [, $taskType] = $this->categoryWithType();

        $this->actingAs($performer)
            ->post(route('task-types.favorite.store', $taskType))
            ->assertRedirect();

        $this->assertDatabaseHas('performer_favorite_task_types', [
            'user_id' => $performer->id,
            'task_type_id' => $taskType->id,
        ]);
    }

    public function test_task_type_favorite_is_not_duplicated(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        [, $taskType] = $this->categoryWithType();

        $this->actingAs($performer)->post(route('task-types.favorite.store', $taskType));
        $this->actingAs($performer)->post(route('task-types.favorite.store', $taskType));

        $this->assertSame(1, PerformerFavoriteTaskType::count());
    }

    public function test_inactive_task_type_cannot_be_favorited(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        [, $taskType] = $this->categoryWithType(['is_active' => false]);

        $this->actingAs($performer)
            ->post(route('task-types.favorite.store', $taskType))
            ->assertNotFound();
    }

    public function test_favorite_categories_filter_uses_performer_favorites(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $parent = Category::factory()->create(['slug' => 'favorite-parent']);
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $other = Category::factory()->create();

        PerformerFavoriteCategory::create(['user_id' => $performer->id, 'category_id' => $parent->id]);
        $this->publishedTask(['category_id' => $child->id, 'title' => 'Из любимой категории']);
        $this->publishedTask(['category_id' => $other->id, 'title' => 'Из другой категории']);

        $titles = $this->taskTitles($this->actingAs($performer)->get('/tasks?favorite_categories=1')->assertOk());

        $this->assertTrue($titles->contains('Из любимой категории'));
        $this->assertFalse($titles->contains('Из другой категории'));
    }

    public function test_favorite_task_types_filter_uses_performer_favorites(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        [$category, $favoriteType] = $this->categoryWithType();
        $otherType = TaskType::factory()->for($category)->create();

        PerformerFavoriteTaskType::create(['user_id' => $performer->id, 'task_type_id' => $favoriteType->id]);
        $this->publishedTask(['category_id' => $category->id, 'task_type_id' => $favoriteType->id, 'title' => 'Любимый вид']);
        $this->publishedTask(['category_id' => $category->id, 'task_type_id' => $otherType->id, 'title' => 'Другой вид']);

        $titles = $this->taskTitles($this->actingAs($performer)->get('/tasks?favorite_types=1')->assertOk());

        $this->assertTrue($titles->contains('Любимый вид'));
        $this->assertFalse($titles->contains('Другой вид'));
    }

    public function test_performer_sees_favorite_summary_on_task_board(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        [$category, $taskType] = $this->categoryWithType();
        PerformerFavoriteCategory::create(['user_id' => $performer->id, 'category_id' => $category->id]);
        PerformerFavoriteTaskType::create(['user_id' => $performer->id, 'task_type_id' => $taskType->id]);

        $response = $this->actingAs($performer)->get('/tasks')->assertOk();

        $this->assertSame(1, $response->inertiaProps('favoritesSummary.category_count'));
        $this->assertSame(1, $response->inertiaProps('favoritesSummary.task_type_count'));
    }

    public function test_guest_task_cards_show_login_cta_for_favorites(): void
    {
        $task = $this->publishedTask();

        $payload = collect($this->get('/tasks')->assertOk()->inertiaProps('tasks'))->firstWhere('id', $task->id);

        $this->assertFalse($payload['favorite']['can_favorite']);
        $this->assertTrue($payload['favorite']['show_login_cta']);
    }

    public function test_performer_task_cards_show_favorite_state(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $task = $this->publishedTask();
        TaskFavorite::create(['user_id' => $performer->id, 'task_id' => $task->id]);

        $payload = collect($this->actingAs($performer)->get('/tasks')->assertOk()->inertiaProps('tasks'))->firstWhere('id', $task->id);

        $this->assertTrue($payload['favorite']['can_favorite']);
        $this->assertTrue($payload['favorite']['is_favorited']);
        $this->assertTrue($payload['badges']['favorited']);
    }

    public function test_performer_can_add_task_to_favorites(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $task = $this->publishedTask();

        $this->actingAs($performer)
            ->post(route('tasks.favorite.store', $task))
            ->assertRedirect();

        $this->assertDatabaseHas('task_favorites', [
            'user_id' => $performer->id,
            'task_id' => $task->id,
        ]);
    }

    public function test_task_favorite_is_not_duplicated(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $task = $this->publishedTask();

        $this->actingAs($performer)->post(route('tasks.favorite.store', $task));
        $this->actingAs($performer)->post(route('tasks.favorite.store', $task));

        $this->assertSame(1, TaskFavorite::count());
    }

    public function test_performer_can_remove_closed_task_from_favorites(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $task = $this->publishedTask(['status' => Task::STATUS_CLOSED]);
        TaskFavorite::create(['user_id' => $performer->id, 'task_id' => $task->id]);

        $this->actingAs($performer)
            ->delete(route('tasks.favorite.destroy', $task))
            ->assertRedirect();

        $this->assertDatabaseCount('task_favorites', 0);
    }

    #[DataProvider('nonPerformerRoles')]
    public function test_only_performer_can_favorite_tasks(string $role): void
    {
        $user = $this->user($role);
        $task = $this->publishedTask();

        $this->actingAs($user)
            ->post(route('tasks.favorite.store', $task))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_task_favorite_route(): void
    {
        $task = $this->publishedTask();

        $this->post(route('tasks.favorite.store', $task))
            ->assertRedirect('/login');
    }

    public function test_closed_task_cannot_be_added_to_favorites(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $task = $this->publishedTask(['status' => Task::STATUS_CLOSED]);

        $this->actingAs($performer)
            ->post(route('tasks.favorite.store', $task))
            ->assertNotFound();
    }

    public function test_performer_favorites_page_shows_saved_tasks_categories_and_types(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        [$category, $taskType] = $this->categoryWithType();
        $task = $this->publishedTask(['category_id' => $category->id, 'task_type_id' => $taskType->id]);
        TaskFavorite::create(['user_id' => $performer->id, 'task_id' => $task->id]);
        PerformerFavoriteCategory::create(['user_id' => $performer->id, 'category_id' => $category->id]);
        PerformerFavoriteTaskType::create(['user_id' => $performer->id, 'task_type_id' => $taskType->id]);

        $response = $this->actingAs($performer)->get('/performer/favorites')->assertOk();

        $this->assertSame('Performer/Favorites/Index', $response->inertiaPage()['component']);
        $this->assertCount(1, $response->inertiaProps('tasks'));
        $this->assertCount(1, $response->inertiaProps('categories'));
        $this->assertCount(1, $response->inertiaProps('taskTypes'));
    }

    public function test_performer_favorites_page_can_filter_closed_tasks(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $activeTask = $this->publishedTask(['title' => 'Активное избранное']);
        $closedTask = $this->publishedTask(['title' => 'Закрытое избранное', 'status' => Task::STATUS_CLOSED]);
        TaskFavorite::create(['user_id' => $performer->id, 'task_id' => $activeTask->id]);
        TaskFavorite::create(['user_id' => $performer->id, 'task_id' => $closedTask->id]);

        $titles = collect($this->actingAs($performer)->get('/performer/favorites?status=closed')->assertOk()->inertiaProps('tasks'))->pluck('title');

        $this->assertTrue($titles->contains('Закрытое избранное'));
        $this->assertFalse($titles->contains('Активное избранное'));
    }

    public function test_task_type_is_required_when_category_has_active_task_types(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER);
        [$category] = $this->categoryWithType();

        $this->actingAs($customer)
            ->from('/customer/tasks/create')
            ->post('/customer/tasks', $this->validTaskPayload($category))
            ->assertRedirect('/customer/tasks/create')
            ->assertSessionHasErrors('task_type_id');
    }

    public function test_customer_can_create_task_with_valid_task_type(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER);
        [$category, $taskType] = $this->categoryWithType();

        $this->actingAs($customer)
            ->post('/customer/tasks', [
                ...$this->validTaskPayload($category),
                'task_type_id' => $taskType->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'user_id' => $customer->id,
            'category_id' => $category->id,
            'task_type_id' => $taskType->id,
            'title' => 'Подготовить аккуратное тестовое задание',
        ]);
    }

    public function test_task_type_must_belong_to_selected_category(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER);
        [$category] = $this->categoryWithType();
        [, $foreignTaskType] = $this->categoryWithType();

        $this->actingAs($customer)
            ->from('/customer/tasks/create')
            ->post('/customer/tasks', [
                ...$this->validTaskPayload($category),
                'task_type_id' => $foreignTaskType->id,
            ])
            ->assertRedirect('/customer/tasks/create')
            ->assertSessionHasErrors('task_type_id');
    }

    public function test_inactive_task_type_is_rejected_on_customer_task_form(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER);
        [$category, $taskType] = $this->categoryWithType(['is_active' => false]);

        $this->actingAs($customer)
            ->from('/customer/tasks/create')
            ->post('/customer/tasks', [
                ...$this->validTaskPayload($category),
                'task_type_id' => $taskType->id,
            ])
            ->assertRedirect('/customer/tasks/create')
            ->assertSessionHasErrors('task_type_id');
    }

    public function test_task_type_is_optional_when_category_has_no_active_task_types(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER);
        $category = Category::factory()->create();

        $this->actingAs($customer)
            ->post('/customer/tasks', $this->validTaskPayload($category))
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'user_id' => $customer->id,
            'category_id' => $category->id,
            'task_type_id' => null,
        ]);
    }

    public function test_task_type_id_does_not_create_contact_guard_flag(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER);
        [$category, $taskType] = $this->categoryWithType();

        $this->actingAs($customer)
            ->post('/customer/tasks', [
                ...$this->validTaskPayload($category),
                'task_type_id' => $taskType->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('moderation_flags', 0);
    }

    public function test_task_board_component_contains_show_all_and_collapse_controls(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Tasks/Index.jsx'));

        $this->assertStringContainsString('Показать все', $source);
        $this->assertStringContainsString('Свернуть', $source);
        $this->assertStringContainsString('Мои избранные направления', $source);
    }

    public function test_task_seeders_create_30_to_50_published_demo_tasks_with_task_types(): void
    {
        $this->seed(DatabaseSeeder::class);

        $publishedWithTypes = Task::published()->whereNotNull('task_type_id')->count();

        $this->assertGreaterThanOrEqual(30, $publishedWithTypes);
        $this->assertLessThanOrEqual(50, $publishedWithTypes);
        $this->assertDatabaseCount('moderation_flags', 0);
    }

    public static function nonPerformerRoles(): array
    {
        return [
            'customer' => [User::ROLE_CUSTOMER],
            'moderator' => [User::ROLE_MODERATOR],
            'admin' => [User::ROLE_ADMIN],
        ];
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * @param  array<string, mixed>  $typeOverrides
     * @return array{0: Category, 1: TaskType}
     */
    private function categoryWithType(array $typeOverrides = []): array
    {
        $category = Category::factory()->create();
        $taskType = TaskType::factory()
            ->for($category)
            ->create($typeOverrides);

        return [$category, $taskType];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function publishedTask(array $overrides = []): Task
    {
        return Task::factory()->create([
            'status' => Task::STATUS_PUBLISHED,
            ...$overrides,
        ]);
    }

    private function taskTitles(TestResponse $response)
    {
        return collect($response->inertiaProps('tasks'))->pluck('title');
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
}
