<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_customer_sees_onboarding_flags(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboards/Customer')
                ->where('onboarding.has_tasks', false)
                ->where('onboarding.has_orders', false));
    }

    public function test_customer_with_task_does_not_get_new_user_flags(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        Task::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('onboarding.has_tasks', true));
    }

    public function test_customer_with_order_does_not_get_new_user_flags(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        Order::factory()->for($customer, 'customer')->create();

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('onboarding.has_orders', true));
    }

    public function test_task_create_page_renders_wizard(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
            ->get(route('customer.tasks.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Customer/Tasks/Create')
                ->has('categories'));
    }
}
