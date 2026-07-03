<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformerDashboardRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_recommends_tasks_from_favorite_categories(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $favorite = Category::factory()->create();
        $other = Category::factory()->create();
        $performer->favoriteCategories()->create(['category_id' => $favorite->id]);

        $matching = $this->publishedTask(['category_id' => $favorite->id, 'title' => 'Подходящее задание']);
        $this->publishedTask(['category_id' => $other->id, 'title' => 'Неподходящее задание']);

        $payload = $this->dashboardTasks($performer);

        $this->assertTrue($payload['has_favorites']);
        $titles = collect($payload['items'])->pluck('title');
        $this->assertTrue($titles->contains('Подходящее задание'));
        $this->assertFalse($titles->contains('Неподходящее задание'));
    }

    public function test_dashboard_includes_tasks_from_child_of_favorite_category(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $performer->favoriteCategories()->create(['category_id' => $parent->id]);

        $this->publishedTask(['category_id' => $child->id, 'title' => 'Задание в подкатегории']);

        $titles = collect($this->dashboardTasks($performer)['items'])->pluck('title');

        $this->assertTrue($titles->contains('Задание в подкатегории'));
    }

    public function test_dashboard_recommends_tasks_from_favorite_task_types(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();
        $taskType = TaskType::factory()->create(['category_id' => $category->id]);
        $performer->favoriteTaskTypes()->create(['task_type_id' => $taskType->id]);

        $this->publishedTask(['category_id' => $category->id, 'task_type_id' => $taskType->id, 'title' => 'Задание по виду']);

        $titles = collect($this->dashboardTasks($performer)['items'])->pluck('title');

        $this->assertTrue($titles->contains('Задание по виду'));
    }

    public function test_dashboard_excludes_tasks_with_own_offer(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $favorite = Category::factory()->create();
        $performer->favoriteCategories()->create(['category_id' => $favorite->id]);

        $offered = $this->publishedTask(['category_id' => $favorite->id, 'title' => 'Уже с моим откликом']);
        TaskOffer::factory()->for($offered)->for($performer, 'performer')->create();

        $titles = collect($this->dashboardTasks($performer)['items'])->pluck('title');

        $this->assertFalse($titles->contains('Уже с моим откликом'));
    }

    public function test_dashboard_falls_back_to_latest_tasks_without_favorites(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $this->publishedTask(['title' => 'Просто свежее задание']);
        Task::factory()->create(['status' => Task::STATUS_DRAFT, 'title' => 'Черновик']);

        $payload = $this->dashboardTasks($performer);

        $this->assertFalse($payload['has_favorites']);
        $titles = collect($payload['items'])->pluck('title');
        $this->assertTrue($titles->contains('Просто свежее задание'));
        $this->assertFalse($titles->contains('Черновик'));
    }

    public function test_dashboard_limits_recommendations_to_five(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        foreach (range(1, 7) as $i) {
            $this->publishedTask(['title' => "Задание {$i}"]);
        }

        $this->assertCount(5, $this->dashboardTasks($performer)['items']);
    }

    private function dashboardTasks(User $performer): array
    {
        return $this->actingAs($performer)
            ->get(route('performer.dashboard'))
            ->assertOk()
            ->inertiaProps('recommendedTasks');
    }

    private function publishedTask(array $overrides = []): Task
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        return Task::factory()->for($customer, 'customer')->create([
            'status' => Task::STATUS_PUBLISHED,
            ...$overrides,
        ]);
    }
}
