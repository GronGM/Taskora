<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ActivityStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_exposes_honest_activity_stats(): void
    {
        Cache::flush();

        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        Task::factory()->for($customer, 'customer')->create(['status' => Task::STATUS_PUBLISHED]);
        Task::factory()->for($customer, 'customer')->create([
            'status' => Task::STATUS_PUBLISHED,
            'created_at' => now()->subDays(10),
        ]);
        Service::factory()->for($performer, 'user')->create(['status' => Service::STATUS_PUBLISHED]);
        Order::factory()->for($customer, 'customer')->for($performer, 'performer')->completed()->create();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('activity.tasks_week', 1)
                ->where('activity.services_published', 1)
                ->where('activity.performers_active', 1)
                ->where('activity.orders_completed', 1));
    }

    public function test_home_stats_count_only_published_content(): void
    {
        Cache::flush();

        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        Task::factory()->for($customer, 'customer')->create(['status' => Task::STATUS_DRAFT]);
        Service::factory()->for($performer, 'user')->create(['status' => Service::STATUS_DRAFT]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('activity.tasks_week', 0)
                ->where('activity.services_published', 0)
                ->where('activity.performers_active', 0));
    }

    public function test_task_board_exposes_weekly_new_tasks_counter(): void
    {
        Cache::flush();

        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        Task::factory()->for($customer, 'customer')->create(['status' => Task::STATUS_PUBLISHED]);

        $this->get('/tasks')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('weeklyNewTasks', 1));
    }
}
