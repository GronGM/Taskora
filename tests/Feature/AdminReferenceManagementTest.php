<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\TaskTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminReferenceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_categories_admin_page(): void
    {
        $category = Category::factory()->create(['name' => 'Учебные презентации']);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin/categories')
            ->assertOk();

        $this->assertSame('Admin/Categories/Index', $response->inertiaPage()['component']);
        $this->assertTrue(collect($response->inertiaProps('categories'))->pluck('name')->contains($category->name));
    }

    public function test_moderator_cannot_see_categories_admin_page(): void
    {
        $this->actingAs($this->user(User::ROLE_MODERATOR))
            ->get('/admin/categories')
            ->assertForbidden();
    }

    #[DataProvider('nonAdminRoles')]
    public function test_customer_and_performer_cannot_see_categories_admin_page(string $role): void
    {
        $this->actingAs($this->user($role))
            ->get('/admin/categories')
            ->assertForbidden();
    }

    public function test_guest_cannot_see_categories_admin_page(): void
    {
        $this->get('/admin/categories')->assertRedirect('/login');
    }

    public function test_admin_can_create_category_with_generated_slug(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->post('/admin/categories', $this->validCategoryPayload([
                'name' => 'Презентации',
                'slug' => '',
            ]))
            ->assertRedirect('/admin/categories');

        $this->assertDatabaseHas('categories', [
            'name' => 'Презентации',
            'slug' => 'prezentacii',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_category_slug_gets_suffix(): void
    {
        Category::factory()->create(['slug' => 'prezentacii']);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->post('/admin/categories', $this->validCategoryPayload([
                'name' => 'Презентации',
                'slug' => '',
            ]))
            ->assertRedirect('/admin/categories');

        $this->assertDatabaseHas('categories', ['slug' => 'prezentacii-2']);
    }

    public function test_admin_can_update_category_without_implicit_slug_change(): void
    {
        $category = Category::factory()->create(['name' => 'Старая категория', 'slug' => 'stable-url']);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->patch(route('admin.categories.update', $category), $this->validCategoryPayload([
                'name' => 'Новое название',
                'slug' => '',
                'sort_order' => 40,
            ]))
            ->assertRedirect('/admin/categories');

        $category->refresh();

        $this->assertSame('Новое название', $category->name);
        $this->assertSame('stable-url', $category->slug);
        $this->assertSame(40, $category->sort_order);
    }

    public function test_admin_can_disable_category(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->post(route('admin.categories.toggle-active', $category))
            ->assertRedirect();

        $this->assertFalse($category->refresh()->is_active);
    }

    public function test_disabled_category_is_not_offered_in_task_create_form(): void
    {
        $active = Category::factory()->create(['name' => 'Активная категория', 'is_active' => true]);
        $inactive = Category::factory()->create(['name' => 'Скрытая категория', 'is_active' => false]);

        $categories = collect($this->actingAs($this->user(User::ROLE_CUSTOMER))
            ->get('/customer/tasks/create')
            ->assertOk()
            ->inertiaProps('categories'));

        $this->assertTrue($categories->pluck('id')->contains($active->id));
        $this->assertFalse($categories->pluck('id')->contains($inactive->id));
    }

    public function test_disabled_category_is_not_shown_as_active_public_filter(): void
    {
        $active = Category::factory()->create(['name' => 'Активная категория', 'is_active' => true]);
        $inactive = Category::factory()->create(['name' => 'Скрытая категория', 'is_active' => false]);

        $categories = collect($this->get('/tasks')->assertOk()->inertiaProps('categories'));

        $this->assertTrue($categories->pluck('id')->contains($active->id));
        $this->assertFalse($categories->pluck('id')->contains($inactive->id));
    }

    public function test_admin_cannot_make_category_parent_itself(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->from(route('admin.categories.edit', $category))
            ->patch(route('admin.categories.update', $category), $this->validCategoryPayload([
                'parent_id' => $category->id,
                'slug' => $category->slug,
            ]))
            ->assertRedirect(route('admin.categories.edit', $category))
            ->assertSessionHasErrors('parent_id');
    }

    public function test_admin_sees_task_types_admin_page(): void
    {
        $category = Category::factory()->create();
        $taskType = TaskType::factory()->for($category)->create(['name' => 'Проверка презентации']);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin/task-types')
            ->assertOk();

        $this->assertSame('Admin/TaskTypes/Index', $response->inertiaPage()['component']);
        $this->assertTrue(collect($response->inertiaProps('taskTypes'))->pluck('name')->contains($taskType->name));
    }

    public function test_moderator_cannot_see_task_types_admin_page(): void
    {
        $this->actingAs($this->user(User::ROLE_MODERATOR))
            ->get('/admin/task-types')
            ->assertForbidden();
    }

    public function test_admin_can_create_task_type(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->post('/admin/task-types', $this->validTaskTypePayload($category, [
                'name' => 'Дизайн слайдов',
                'slug' => '',
            ]))
            ->assertRedirect('/admin/task-types');

        $this->assertDatabaseHas('task_types', [
            'category_id' => $category->id,
            'name' => 'Дизайн слайдов',
            'slug' => 'dizayn-slaydov',
            'is_active' => true,
        ]);
    }

    public function test_task_type_requires_category(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->from('/admin/task-types/create')
            ->post('/admin/task-types', [
                ...$this->validTaskTypePayload(Category::factory()->create()),
                'category_id' => '',
            ])
            ->assertRedirect('/admin/task-types/create')
            ->assertSessionHasErrors('category_id');
    }

    public function test_task_type_cannot_attach_to_inactive_category(): void
    {
        $category = Category::factory()->create(['is_active' => false]);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->from('/admin/task-types/create')
            ->post('/admin/task-types', $this->validTaskTypePayload($category))
            ->assertRedirect('/admin/task-types/create')
            ->assertSessionHasErrors('category_id');
    }

    public function test_admin_can_update_task_type_without_implicit_slug_change(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $taskType = TaskType::factory()->for($category)->create([
            'name' => 'Старый вид',
            'slug' => 'stable-type-url',
        ]);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->patch(route('admin.task-types.update', $taskType), $this->validTaskTypePayload($category, [
                'name' => 'Новый вид',
                'slug' => '',
                'sort_order' => 50,
            ]))
            ->assertRedirect('/admin/task-types');

        $taskType->refresh();

        $this->assertSame('Новый вид', $taskType->name);
        $this->assertSame('stable-type-url', $taskType->slug);
        $this->assertSame(50, $taskType->sort_order);
    }

    public function test_admin_can_disable_task_type(): void
    {
        $category = Category::factory()->create();
        $taskType = TaskType::factory()->for($category)->create(['is_active' => true]);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->post(route('admin.task-types.toggle-active', $taskType))
            ->assertRedirect();

        $this->assertFalse($taskType->refresh()->is_active);
    }

    public function test_disabled_task_type_is_not_offered_in_task_create_form(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $active = TaskType::factory()->for($category)->create(['name' => 'Активный вид', 'is_active' => true]);
        $inactive = TaskType::factory()->for($category)->create(['name' => 'Скрытый вид', 'is_active' => false]);

        $categoryPayload = collect($this->actingAs($this->user(User::ROLE_CUSTOMER))
            ->get('/customer/tasks/create')
            ->assertOk()
            ->inertiaProps('categories'))
            ->firstWhere('id', $category->id);

        $taskTypeIds = collect($categoryPayload['task_types'])->pluck('id');

        $this->assertTrue($taskTypeIds->contains($active->id));
        $this->assertFalse($taskTypeIds->contains($inactive->id));
    }

    public function test_disabled_task_type_is_not_shown_as_active_public_filter(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $active = TaskType::factory()->for($category)->create(['is_active' => true]);
        $inactive = TaskType::factory()->for($category)->create(['is_active' => false]);

        $taskTypeIds = collect($this->get('/tasks')->assertOk()->inertiaProps('taskTypes'))->pluck('id');

        $this->assertTrue($taskTypeIds->contains($active->id));
        $this->assertFalse($taskTypeIds->contains($inactive->id));
    }

    public function test_task_type_duplicate_slug_gets_suffix(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        TaskType::factory()->for($category)->create(['slug' => 'dizayn-slaydov']);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->post('/admin/task-types', $this->validTaskTypePayload($category, [
                'name' => 'Дизайн слайдов',
                'slug' => '',
            ]))
            ->assertRedirect('/admin/task-types');

        $this->assertDatabaseHas('task_types', ['slug' => 'dizayn-slaydov-2']);
    }

    public function test_contact_guard_blocks_category_description(): void
    {
        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->from('/admin/categories/create')
            ->post('/admin/categories', $this->validCategoryPayload([
                'name' => 'Категория с контактами',
                'description' => 'Пишите на admin@example.com для деталей.',
            ]))
            ->assertRedirect('/admin/categories/create')
            ->assertSessionHasErrors('description');

        $this->assertDatabaseMissing('categories', ['name' => 'Категория с контактами']);
    }

    public function test_contact_guard_blocks_task_type_description(): void
    {
        $category = Category::factory()->create(['is_active' => true]);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->from('/admin/task-types/create')
            ->post('/admin/task-types', $this->validTaskTypePayload($category, [
                'name' => 'Вид с контактами',
                'description' => 'Напишите на admin@example.com для деталей.',
            ]))
            ->assertRedirect('/admin/task-types/create')
            ->assertSessionHasErrors('description');

        $this->assertDatabaseMissing('task_types', ['name' => 'Вид с контактами']);
    }

    public function test_move_up_and_move_down_change_sort_order(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $first = Category::factory()->create(['sort_order' => 1000, 'name' => 'А первая']);
        $second = Category::factory()->create(['sort_order' => 2000, 'name' => 'Б вторая']);
        $category = Category::factory()->create(['is_active' => true, 'sort_order' => 3000]);
        $firstType = TaskType::factory()->for($category)->create(['sort_order' => 1000, 'name' => 'А первый']);
        $secondType = TaskType::factory()->for($category)->create(['sort_order' => 2000, 'name' => 'Б второй']);

        $this->actingAs($admin)->post(route('admin.categories.move-up', $second))->assertRedirect();
        $this->assertSame(1000, $second->refresh()->sort_order);
        $this->assertSame(2000, $first->refresh()->sort_order);

        $this->actingAs($admin)->post(route('admin.task-types.move-up', $secondType))->assertRedirect();
        $this->assertSame(1000, $secondType->refresh()->sort_order);
        $this->assertSame(2000, $firstType->refresh()->sort_order);
    }

    public function test_tasks_still_open_after_category_and_type_disabled(): void
    {
        $category = Category::factory()->create(['is_active' => false]);
        $taskType = TaskType::factory()->for($category)->create(['is_active' => false]);
        $task = Task::factory()->create([
            'category_id' => $category->id,
            'task_type_id' => $taskType->id,
            'title' => 'Опубликованное задание со скрытыми справочниками',
            'status' => Task::STATUS_PUBLISHED,
        ]);

        $response = $this->get('/tasks')->assertOk();

        $this->assertTrue(collect($response->inertiaProps('tasks'))->pluck('id')->contains($task->id));
        $this->get($task->url)->assertOk();
    }

    public function test_seeders_still_create_categories_and_task_types(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(TaskTypeSeeder::class);

        $this->assertGreaterThan(0, Category::count());
        $this->assertGreaterThan(0, TaskType::count());
    }

    public static function nonAdminRoles(): array
    {
        return [
            'customer' => [User::ROLE_CUSTOMER],
            'performer' => [User::ROLE_PERFORMER],
        ];
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validCategoryPayload(array $overrides = []): array
    {
        return [
            'name' => 'Тестовая категория',
            'slug' => 'testovaya-kategoriya',
            'parent_id' => null,
            'description' => 'Описание категории для публичных страниц Таскоры.',
            'icon' => 'book-open',
            'sort_order' => 10,
            'is_active' => true,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validTaskTypePayload(Category $category, array $overrides = []): array
    {
        return [
            'category_id' => $category->id,
            'name' => 'Тестовый вид задания',
            'slug' => 'testovyy-vid-zadaniya',
            'description' => 'Описание вида задания без контактных данных.',
            'sort_order' => 10,
            'is_active' => true,
            ...$overrides,
        ];
    }
}
