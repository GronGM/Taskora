<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_performer_sees_onboarding_flags(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        $this->actingAs($performer)
            ->get(route('performer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboards/Performer')
                ->where('onboarding.has_profile', false)
                ->where('onboarding.has_services', false)
                ->where('onboarding.has_offers', false));
    }

    public function test_performer_with_service_gets_has_services_flag(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        Service::factory()->for($performer, 'user')->create();

        $this->actingAs($performer)
            ->get(route('performer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('onboarding.has_services', true));
    }

    public function test_performer_with_offer_gets_has_offers_flag(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $task = Task::factory()->create(['status' => Task::STATUS_PUBLISHED]);
        TaskOffer::factory()->for($task)->for($performer, 'performer')->create();

        $this->actingAs($performer)
            ->get(route('performer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('onboarding.has_offers', true));
    }

    public function test_service_create_page_renders_wizard(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        $this->actingAs($performer)
            ->get(route('performer.services.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Performer/Services/Create')
                ->has('categories')
                ->has('defaultPackages'));
    }
}
