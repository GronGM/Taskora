<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ModerationFlag;
use App\Models\Order;
use App\Models\PerformerPortfolioItem;
use App\Models\PerformerProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformerProfileVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_performer_can_open_profile(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);

        $response = $this->actingAs($performer)->get('/performer/profile')->assertOk();

        $this->assertSame('Performer/Profile/Show', $response->inertiaPage()['component']);
        $this->assertDatabaseHas('performer_profiles', ['user_id' => $performer->id]);
    }

    public function test_customer_cannot_open_profile(): void
    {
        $this->actingAs($this->user(User::ROLE_CUSTOMER))
            ->get('/performer/profile')
            ->assertForbidden();
    }

    public function test_performer_can_update_own_profile(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $profile = $this->profile($performer);
        $category = $this->category();

        $this->actingAs($performer)
            ->patch('/performer/profile', $this->profilePayload([$category->id], [
                'display_name' => 'Публичный исполнитель',
            ]))
            ->assertRedirect(route('performer.profile.show'));

        $this->assertDatabaseHas('performer_profiles', [
            'id' => $profile->id,
            'display_name' => 'Публичный исполнитель',
        ]);
    }

    public function test_profile_with_contact_data_is_blocked(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $profile = $this->profile($performer);
        $category = $this->category();

        $this->actingAs($performer)
            ->patch('/performer/profile', $this->profilePayload([$category->id], [
                'bio' => 'Пишите на test@example.com, так быстрее договоримся по работе внутри проекта.',
            ]))
            ->assertSessionHasErrors('bio');

        $this->assertDatabaseMissing('performer_profiles', [
            'id' => $profile->id,
            'bio' => 'Пишите на test@example.com, так быстрее договоримся по работе внутри проекта.',
        ]);
    }

    public function test_profile_contact_block_creates_moderation_flag(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $profile = $this->profile($performer);
        $category = $this->category();

        $this->actingAs($performer)
            ->patch('/performer/profile', $this->profilePayload([$category->id], [
                'headline' => 'Пишите в Telegram @taskora_test',
            ]))
            ->assertSessionHasErrors('headline');

        $this->assertDatabaseHas('moderation_flags', [
            'entity_type' => PerformerProfile::class,
            'entity_id' => $profile->id,
            'reason' => 'contact_detected_in_performer_profile',
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }

    public function test_performer_can_select_specializations(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $profile = $this->profile($performer);
        $categories = Category::factory()->count(2)->create(['is_active' => true]);

        $this->actingAs($performer)
            ->patch('/performer/profile', $this->profilePayload($categories->pluck('id')->all()))
            ->assertRedirect(route('performer.profile.show'));

        $this->assertSame(2, $profile->fresh()->specializations()->count());
    }

    public function test_performer_cannot_select_more_than_seven_specializations(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $this->profile($performer);
        $categories = Category::factory()->count(8)->create(['is_active' => true]);

        $this->actingAs($performer)
            ->patch('/performer/profile', $this->profilePayload($categories->pluck('id')->all()))
            ->assertSessionHasErrors('specialization_ids');
    }

    public function test_performer_cannot_select_inactive_specialization(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $this->profile($performer);
        $inactiveCategory = Category::factory()->create(['is_active' => false]);

        $this->actingAs($performer)
            ->patch('/performer/profile', $this->profilePayload([$inactiveCategory->id]))
            ->assertSessionHasErrors('specialization_ids.0');
    }

    public function test_performer_can_submit_verification_when_requirements_are_met(): void
    {
        $profile = $this->readyProfile();

        $this->actingAs($profile->user)
            ->post('/performer/profile/submit-verification')
            ->assertRedirect(route('performer.profile.show'));

        $this->assertSame(PerformerProfile::STATUS_PENDING_REVIEW, $profile->fresh()->verification_status);
    }

    public function test_bio_minimum_is_required_for_verification(): void
    {
        $profile = $this->readyProfile(attributes: ['bio' => 'Коротко.']);

        $this->actingAs($profile->user)
            ->post('/performer/profile/submit-verification')
            ->assertSessionHasErrors('bio');
    }

    public function test_specialization_is_required_for_verification(): void
    {
        $profile = $this->readyProfile(withSpecialization: false);

        $this->actingAs($profile->user)
            ->post('/performer/profile/submit-verification')
            ->assertSessionHasErrors('specialization_ids');
    }

    public function test_portfolio_or_published_service_is_required_for_verification(): void
    {
        $profile = $this->readyProfile(withPortfolio: false);

        $this->actingAs($profile->user)
            ->post('/performer/profile/submit-verification')
            ->assertSessionHasErrors('proof');
    }

    public function test_moderator_sees_pending_profiles(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_PENDING_REVIEW);

        $response = $this->actingAs($this->user(User::ROLE_MODERATOR))
            ->get('/moderator/performer-profiles')
            ->assertOk();

        $this->assertSame('Moderator/PerformerProfiles/Index', $response->inertiaPage()['component']);
        $this->assertTrue(collect($response->inertiaProps('profiles'))->pluck('id')->contains($profile->id));
    }

    public function test_admin_sees_pending_profiles(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_PENDING_REVIEW);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/moderator/performer-profiles')
            ->assertOk();

        $this->assertTrue(collect($response->inertiaProps('profiles'))->pluck('id')->contains($profile->id));
    }

    public function test_customer_and_performer_cannot_open_moderator_profile_queue(): void
    {
        $this->readyProfile(status: PerformerProfile::STATUS_PENDING_REVIEW);

        $this->actingAs($this->user(User::ROLE_CUSTOMER))
            ->get('/moderator/performer-profiles')
            ->assertForbidden();

        $this->actingAs($this->user(User::ROLE_PERFORMER))
            ->get('/moderator/performer-profiles')
            ->assertForbidden();
    }

    public function test_moderator_can_approve_profile(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_PENDING_REVIEW);
        $moderator = $this->user(User::ROLE_MODERATOR);

        $this->actingAs($moderator)
            ->post(route('moderator.performer-profiles.approve', $profile))
            ->assertRedirect(route('moderator.performer-profiles.index'));
    }

    public function test_approve_sets_verification_status_verified(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_PENDING_REVIEW);

        $this->actingAs($this->user(User::ROLE_MODERATOR))
            ->post(route('moderator.performer-profiles.approve', $profile));

        $this->assertSame(PerformerProfile::STATUS_VERIFIED, $profile->fresh()->verification_status);
    }

    public function test_approve_writes_verified_at_and_verified_by(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_PENDING_REVIEW);
        $moderator = $this->user(User::ROLE_MODERATOR);

        $this->actingAs($moderator)
            ->post(route('moderator.performer-profiles.approve', $profile));

        $profile->refresh();
        $this->assertNotNull($profile->verified_at);
        $this->assertSame($moderator->id, $profile->verified_by);
    }

    public function test_moderator_can_reject_profile_with_reason(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_PENDING_REVIEW);

        $this->actingAs($this->user(User::ROLE_MODERATOR))
            ->post(route('moderator.performer-profiles.reject', $profile), [
                'reason' => 'Нужно подробнее описать опыт и примеры работ.',
            ])
            ->assertRedirect(route('moderator.performer-profiles.index'));

        $this->assertSame(PerformerProfile::STATUS_REJECTED, $profile->fresh()->verification_status);
    }

    public function test_reject_reason_is_required(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_PENDING_REVIEW);

        $this->actingAs($this->user(User::ROLE_MODERATOR))
            ->post(route('moderator.performer-profiles.reject', $profile), ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    public function test_rejected_reason_is_visible_to_performer(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_REJECTED, attributes: [
            'verification_note' => 'Добавьте больше деталей о портфолио.',
        ]);

        $response = $this->actingAs($profile->user)->get('/performer/profile')->assertOk();

        $this->assertSame('Добавьте больше деталей о портфолио.', $response->inertiaProps('profile.verification_note'));
    }

    public function test_approve_and_reject_notify_performer(): void
    {
        $moderator = $this->user(User::ROLE_MODERATOR);
        $approved = $this->readyProfile(status: PerformerProfile::STATUS_PENDING_REVIEW);
        $rejected = $this->readyProfile(status: PerformerProfile::STATUS_PENDING_REVIEW);

        $this->actingAs($moderator)->post(route('moderator.performer-profiles.approve', $approved));
        $this->actingAs($moderator)->post(route('moderator.performer-profiles.reject', $rejected), [
            'reason' => 'Нужно подробнее описать опыт и примеры работ.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $approved->user_id,
            'notifiable_type' => User::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $rejected->user_id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_submit_verification_notifies_moderator_and_admin(): void
    {
        $moderator = $this->user(User::ROLE_MODERATOR);
        $admin = $this->user(User::ROLE_ADMIN);
        $profile = $this->readyProfile();

        $this->actingAs($profile->user)
            ->post('/performer/profile/submit-verification');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $moderator->id,
            'notifiable_type' => User::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_performer_can_create_portfolio_item(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $profile = $this->profile($performer);
        $category = $this->category();

        $this->actingAs($performer)
            ->post('/performer/portfolio', [
                'title' => 'Пример презентации',
                'description' => 'Публичное описание работы без контактов.',
                'category_id' => $category->id,
                'status' => PerformerPortfolioItem::STATUS_PUBLISHED,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('performer_portfolio_items', [
            'performer_profile_id' => $profile->id,
            'title' => 'Пример презентации',
            'status' => PerformerPortfolioItem::STATUS_PUBLISHED,
        ]);
    }

    public function test_portfolio_item_with_contact_data_is_blocked(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER);
        $this->profile($performer);

        $this->actingAs($performer)
            ->post('/performer/portfolio', [
                'title' => 'Пишите на test@example.com',
                'description' => 'Описание работы.',
                'status' => PerformerPortfolioItem::STATUS_PUBLISHED,
            ])
            ->assertSessionHasErrors('title');

        $this->assertDatabaseMissing('performer_portfolio_items', ['title' => 'Пишите на test@example.com']);
        $this->assertDatabaseHas('moderation_flags', [
            'entity_type' => PerformerPortfolioItem::class,
            'reason' => 'contact_detected_in_portfolio',
        ]);
    }

    public function test_performer_cannot_edit_foreign_portfolio_item(): void
    {
        $ownerProfile = $this->readyProfile();
        $foreignPerformer = $this->user(User::ROLE_PERFORMER);

        $item = $ownerProfile->portfolioItems()->firstOrFail();

        $this->actingAs($foreignPerformer)
            ->get(route('performer.portfolio.edit', $item))
            ->assertForbidden();
    }

    public function test_hidden_and_draft_portfolio_items_are_not_public(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_VERIFIED, withPortfolio: false);
        PerformerPortfolioItem::factory()->for($profile, 'profile')->hidden()->create(['title' => 'Скрытая работа']);
        PerformerPortfolioItem::factory()->for($profile, 'profile')->draft()->create(['title' => 'Черновик работы']);

        $response = $this->get(route('performers.show', $profile->user))->assertOk();

        $titles = collect($response->inertiaProps('portfolio'))->pluck('title');
        $this->assertFalse($titles->contains('Скрытая работа'));
        $this->assertFalse($titles->contains('Черновик работы'));
    }

    public function test_published_portfolio_item_is_public(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_VERIFIED, withPortfolio: false);
        PerformerPortfolioItem::factory()->for($profile, 'profile')->create(['title' => 'Публичная работа']);

        $response = $this->get(route('performers.show', $profile->user))->assertOk();

        $this->assertTrue(collect($response->inertiaProps('portfolio'))->pluck('title')->contains('Публичная работа'));
    }

    public function test_performers_index_shows_verified_badge(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_VERIFIED);

        $response = $this->get('/performers')->assertOk();

        $payload = collect($response->inertiaProps('performers'))->firstWhere('id', $profile->user_id);
        $this->assertTrue($payload['is_verified']);
    }

    public function test_public_profile_shows_profile_services_reviews_and_portfolio(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_VERIFIED);
        $category = $profile->specializations()->first();
        $service = Service::factory()->for($profile->user, 'user')->for($category)->create([
            'title' => 'Публичная услуга профиля',
            'status' => Service::STATUS_PUBLISHED,
        ]);
        $order = Order::factory()->completed()->create([
            'customer_id' => $this->user(User::ROLE_CUSTOMER)->id,
            'performer_id' => $profile->user_id,
            'category_id' => $category->id,
            'service_id' => $service->id,
        ]);
        Review::query()->create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'task_id' => null,
            'customer_id' => $order->customer_id,
            'performer_id' => $profile->user_id,
            'rating' => 5,
            'comment' => 'Отличная работа внутри платформы.',
            'status' => Review::STATUS_PUBLISHED,
            'is_public' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('performers.show', $profile->user))->assertOk();

        $this->assertSame('Performers/Show', $response->inertiaPage()['component']);
        $this->assertSame($profile->display_name, $response->inertiaProps('performer.name'));
        $this->assertTrue(collect($response->inertiaProps('services'))->pluck('title')->contains($service->title));
        $this->assertTrue(collect($response->inertiaProps('reviews'))->pluck('comment')->contains('Отличная работа внутри платформы.'));
        $this->assertNotEmpty($response->inertiaProps('portfolio'));
    }

    public function test_public_profile_does_not_show_user_email(): void
    {
        $profile = $this->readyProfile(status: PerformerProfile::STATUS_VERIFIED);

        $response = $this->get(route('performers.show', $profile->user))->assertOk();

        $this->assertStringNotContainsString($profile->user->email, $response->getContent());
    }

    public function test_demo_seed_creates_verified_profile_and_portfolio(): void
    {
        $this->seed();

        $performer = User::query()->where('email', 'performer@taskora.local')->firstOrFail();
        $profile = $performer->performerProfile()->firstOrFail();

        $this->assertSame(PerformerProfile::STATUS_VERIFIED, $profile->verification_status);
        $this->assertGreaterThanOrEqual(2, $profile->portfolioItems()->where('status', PerformerPortfolioItem::STATUS_PUBLISHED)->count());
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function category(array $attributes = []): Category
    {
        return Category::factory()->create([
            'is_active' => true,
            ...$attributes,
        ]);
    }

    private function profile(User $performer, array $attributes = []): PerformerProfile
    {
        return PerformerProfile::factory()
            ->for($performer, 'user')
            ->create([
                'display_name' => 'Исполнитель теста',
                'headline' => 'Документы и презентации',
                'bio' => $this->longBio(),
                'portfolio_summary' => 'Публичные работы без контактных данных.',
                ...$attributes,
            ]);
    }

    private function readyProfile(
        string $status = PerformerProfile::STATUS_NOT_SUBMITTED,
        array $attributes = [],
        bool $withSpecialization = true,
        bool $withPortfolio = true,
    ): PerformerProfile {
        $performer = $this->user(User::ROLE_PERFORMER);
        $category = $this->category();
        $statusAttributes = match ($status) {
            PerformerProfile::STATUS_PENDING_REVIEW => ['submitted_for_verification_at' => now()],
            PerformerProfile::STATUS_VERIFIED => ['verified_at' => now(), 'published_at' => now()],
            default => [],
        };

        $profile = $this->profile($performer, [
            'verification_status' => $status,
            ...$statusAttributes,
            ...$attributes,
        ]);

        if ($withSpecialization) {
            $profile->specializations()->sync([$category->id]);
        }

        if ($withPortfolio) {
            PerformerPortfolioItem::factory()
                ->for($profile, 'profile')
                ->for($category)
                ->create(['title' => 'Опубликованная работа портфолио']);
        }

        return $profile->refresh()->load('user');
    }

    /**
     * @param  array<int, int>  $specializationIds
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profilePayload(array $specializationIds = [], array $overrides = []): array
    {
        return [
            'display_name' => 'Исполнитель теста',
            'headline' => 'Документы и презентации',
            'bio' => $this->longBio(),
            'experience_years' => 4,
            'response_time_label' => 'Отвечает в течение дня',
            'portfolio_summary' => 'Публичные работы без контактных данных.',
            'specialization_ids' => $specializationIds,
            ...$overrides,
        ];
    }

    private function longBio(): string
    {
        return 'Помогаю готовить документы, презентации и таблицы для учебных и рабочих задач. Аккуратно структурирую материалы, оформляю результат по требованиям и веду обсуждение только внутри Таскоры.';
    }
}
