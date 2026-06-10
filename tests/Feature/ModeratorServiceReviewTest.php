<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ModerationFlag;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeratorServiceReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_see_pending_review_services(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $service = $this->pendingService();

        $response = $this->actingAs($moderator)
            ->get('/moderator/services')
            ->assertOk();

        $this->assertSame('Moderator/Services/Index', $response->inertiaPage()['component']);
        $this->assertTrue(
            collect($response->inertiaProps('services'))->pluck('title')->contains($service->title),
        );
    }

    public function test_admin_can_see_pending_review_services(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $service = $this->pendingService();

        $response = $this->actingAs($admin)
            ->get('/moderator/services')
            ->assertOk();

        $this->assertTrue(
            collect($response->inertiaProps('services'))->pluck('title')->contains($service->title),
        );
    }

    public function test_performer_cannot_open_moderator_services(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        $this->actingAs($performer)
            ->get('/moderator/services')
            ->assertForbidden();
    }

    public function test_customer_cannot_open_moderator_services(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
            ->get('/moderator/services')
            ->assertForbidden();
    }

    public function test_guest_cannot_open_moderator_services(): void
    {
        $this->get('/moderator/services')
            ->assertRedirect('/login');
    }

    public function test_moderator_can_open_pending_review_service(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $service = $this->pendingService();

        $response = $this->actingAs($moderator)
            ->get(route('moderator.services.show', $service))
            ->assertOk();

        $this->assertSame('Moderator/Services/Show', $response->inertiaPage()['component']);
        $this->assertSame($service->title, $response->inertiaProps('service.title'));
    }

    public function test_moderator_can_approve_service(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $service = $this->pendingService(['rejection_reason' => 'Старый комментарий модерации']);

        $this->actingAs($moderator)
            ->post(route('moderator.services.approve', $service))
            ->assertRedirect(route('moderator.services.index'));

        $service->refresh();

        $this->assertSame(Service::STATUS_PUBLISHED, $service->status);
        $this->assertNull($service->rejection_reason);
        $this->assertSame($moderator->id, $service->moderated_by);
        $this->assertNotNull($service->moderated_at);
    }

    public function test_approved_service_appears_in_public_catalog(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $service = $this->pendingService(['title' => 'Одобренная услуга для каталога']);

        $this->actingAs($moderator)
            ->post(route('moderator.services.approve', $service))
            ->assertRedirect();

        $response = $this->get('/catalog')->assertOk();

        $this->assertTrue(
            collect($response->inertiaProps('services'))->pluck('title')->contains('Одобренная услуга для каталога'),
        );
    }

    public function test_moderator_can_reject_service_with_reason(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $service = $this->pendingService();

        $this->actingAs($moderator)
            ->post(route('moderator.services.reject', $service), [
                'reason' => 'Описание услуги нужно уточнить перед публикацией.',
            ])
            ->assertRedirect(route('moderator.services.index'));

        $service->refresh();

        $this->assertSame(Service::STATUS_REJECTED, $service->status);
        $this->assertSame('Описание услуги нужно уточнить перед публикацией.', $service->rejection_reason);
        $this->assertSame($moderator->id, $service->moderated_by);
        $this->assertNotNull($service->moderated_at);
    }

    public function test_rejected_service_is_not_publicly_visible(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $service = $this->pendingService(['title' => 'Отклоненная невидимая услуга']);

        $this->actingAs($moderator)
            ->post(route('moderator.services.reject', $service), [
                'reason' => 'Недостаточно конкретики для публикации услуги.',
            ])
            ->assertRedirect();

        $response = $this->get('/catalog')->assertOk();

        $this->assertFalse(
            collect($response->inertiaProps('services'))->pluck('title')->contains('Отклоненная невидимая услуга'),
        );
    }

    public function test_rejection_reason_is_visible_to_performer(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $service = $this->pendingService([
            'user_id' => $performer->id,
            'status' => Service::STATUS_REJECTED,
            'rejection_reason' => 'Добавьте больше деталей о результате работы.',
        ]);

        $response = $this->actingAs($performer)
            ->get('/performer/services')
            ->assertOk();

        $servicePayload = collect($response->inertiaProps('services'))
            ->firstWhere('id', $service->id);

        $this->assertSame('Добавьте больше деталей о результате работы.', $servicePayload['rejection_reason']);
    }

    public function test_reject_without_valid_reason_fails_validation(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $service = $this->pendingService();

        $this->actingAs($moderator)
            ->from(route('moderator.services.show', $service))
            ->post(route('moderator.services.reject', $service), [
                'reason' => 'коротко',
            ])
            ->assertRedirect(route('moderator.services.show', $service))
            ->assertSessionHasErrors('reason');

        $this->assertSame(Service::STATUS_PENDING_REVIEW, $service->refresh()->status);
    }

    public function test_moderator_can_resolve_moderation_flag(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $service = $this->pendingService();
        $flag = ModerationFlag::create([
            'user_id' => $service->user_id,
            'entity_type' => Service::class,
            'entity_id' => $service->id,
            'reason' => 'contact_detected_in_service',
            'matched_type' => 'telegram',
            'matched_value' => '@taskora_helper',
            'status' => ModerationFlag::STATUS_OPEN,
        ]);

        $this->actingAs($moderator)
            ->post(route('moderator.moderation-flags.resolve', $flag))
            ->assertRedirect(route('moderator.moderation-flags.index'));

        $flag->refresh();

        $this->assertSame(ModerationFlag::STATUS_RESOLVED, $flag->status);
        $this->assertSame($moderator->id, $flag->resolved_by);
        $this->assertNotNull($flag->resolved_at);
    }

    public function test_performer_cannot_approve_own_service_by_direct_post(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $service = $this->pendingService(['user_id' => $performer->id]);

        $this->actingAs($performer)
            ->post(route('moderator.services.approve', $service))
            ->assertForbidden();

        $this->assertSame(Service::STATUS_PENDING_REVIEW, $service->refresh()->status);
    }

    public function test_non_pending_service_cannot_be_approved(): void
    {
        $moderator = User::factory()->create(['role' => User::ROLE_MODERATOR]);
        $service = $this->pendingService(['status' => Service::STATUS_DRAFT]);

        $this->actingAs($moderator)
            ->post(route('moderator.services.approve', $service))
            ->assertForbidden();

        $this->assertSame(Service::STATUS_DRAFT, $service->refresh()->status);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function pendingService(array $overrides = []): Service
    {
        $service = Service::factory()
            ->for(Category::factory())
            ->create([
                'status' => Service::STATUS_PENDING_REVIEW,
                ...$overrides,
            ]);

        ServicePackage::factory()->for($service)->create();

        return $service;
    }
}
