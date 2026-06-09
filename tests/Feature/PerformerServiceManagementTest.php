<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ModerationFlag;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PerformerServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_performer_can_open_own_services_list(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        $this->actingAs($performer)
            ->get('/performer/services')
            ->assertOk();
    }

    public function test_customer_cannot_open_performer_services(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
            ->get('/performer/services')
            ->assertForbidden();
    }

    public function test_performer_can_create_service_with_one_package(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();

        $response = $this->actingAs($performer)
            ->post('/performer/services', $this->validPayload($category));

        $service = Service::where('title', 'Настрою аккуратную услугу')->firstOrFail();

        $response->assertRedirect(route('performer.services.edit', $service));
        $this->assertSame(Service::STATUS_DRAFT, $service->status);
        $this->assertSame(1, $service->packages()->count());
        $this->assertSame($performer->id, $service->user_id);
    }

    public function test_new_service_gets_draft_status(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();

        $this->actingAs($performer)
            ->post('/performer/services', $this->validPayload($category))
            ->assertRedirect();

        $this->assertDatabaseHas('services', [
            'title' => 'Настрою аккуратную услугу',
            'status' => Service::STATUS_DRAFT,
        ]);
    }

    public function test_performer_can_submit_own_service_for_review(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $service = Service::factory()->for($performer, 'user')->create(['status' => Service::STATUS_DRAFT]);

        $this->actingAs($performer)
            ->post(route('performer.services.submit-review', $service))
            ->assertRedirect(route('performer.services.index'));

        $this->assertSame(Service::STATUS_PENDING_REVIEW, $service->refresh()->status);
    }

    public function test_published_service_cannot_be_manually_submitted_for_review(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $service = Service::factory()->for($performer, 'user')->create(['status' => Service::STATUS_PUBLISHED]);

        $this->actingAs($performer)
            ->post(route('performer.services.submit-review', $service))
            ->assertForbidden();

        $this->assertSame(Service::STATUS_PUBLISHED, $service->refresh()->status);
    }

    public function test_editing_important_fields_of_published_service_returns_it_to_review(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();
        $service = Service::factory()->for($performer, 'user')->for($category)->create([
            'status' => Service::STATUS_PUBLISHED,
        ]);

        $this->actingAs($performer)
            ->put(route('performer.services.update', $service), [
                ...$this->validPayload($category),
                'title' => 'Обновленная опубликованная услуга',
            ])
            ->assertRedirect(route('performer.services.edit', $service));

        $this->assertSame(Service::STATUS_PENDING_REVIEW, $service->refresh()->status);
    }

    public function test_performer_cannot_edit_foreign_service(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $otherPerformer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $service = Service::factory()->for($otherPerformer, 'user')->create();

        $this->actingAs($performer)
            ->get(route('performer.services.edit', $service))
            ->assertForbidden();
    }

    public function test_draft_and_pending_review_services_are_not_publicly_visible(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();

        $draft = Service::factory()->for($performer, 'user')->for($category)->create([
            'title' => 'Скрытый черновик',
            'status' => Service::STATUS_DRAFT,
        ]);
        $pending = Service::factory()->for($performer, 'user')->for($category)->create([
            'title' => 'Скрытая услуга на модерации',
            'status' => Service::STATUS_PENDING_REVIEW,
        ]);

        $response = $this->get('/catalog')->assertOk();
        $titles = collect($response->inertiaProps('services'))->pluck('title');

        $this->assertFalse($titles->contains($draft->title));
        $this->assertFalse($titles->contains($pending->title));
    }

    public function test_published_service_is_publicly_visible(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();

        $published = Service::factory()->for($performer, 'user')->for($category)->create([
            'title' => 'Опубликованная тестовая услуга',
            'status' => Service::STATUS_PUBLISHED,
        ]);

        ServicePackage::factory()->for($published)->create();

        $response = $this->get('/catalog')->assertOk();

        $this->assertTrue(
            collect($response->inertiaProps('services'))->pluck('title')->contains('Опубликованная тестовая услуга'),
        );
    }

    #[DataProvider('contactViolations')]
    public function test_service_with_contact_in_description_is_not_saved(string $description, string $matchedType): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();

        $this->actingAs($performer)
            ->from('/performer/services/create')
            ->post('/performer/services', [
                ...$this->validPayload($category),
                'description' => $description,
            ])
            ->assertRedirect('/performer/services/create')
            ->assertSessionHasErrors('description');

        $this->assertDatabaseMissing('services', [
            'title' => 'Настрою аккуратную услугу',
        ]);

        $this->assertDatabaseHas('moderation_flags', [
            'user_id' => $performer->id,
            'entity_type' => Service::class,
            'reason' => 'contact_detected_in_service',
            'matched_type' => $matchedType,
            'status' => 'open',
        ]);
        $this->assertSame(1, ModerationFlag::count());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function contactViolations(): array
    {
        return [
            'email' => ['Напишите мне на test@example.com, чтобы обсудить детали.', 'email'],
            'phone' => ['Созвонимся по номеру +7 999 123-45-67 перед стартом.', 'phone'],
            'telegram' => ['Для деталей напиши в тг @taskora_helper.', 'telegram'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Category $category): array
    {
        return [
            'category_id' => $category->id,
            'title' => 'Настрою аккуратную услугу',
            'short_description' => 'Сделаю понятное описание результата и подготовлю материалы по заданию.',
            'description' => 'Подготовлю результат внутри платформы, согласую структуру и передам итоговые материалы через рабочую область.',
            'price_from' => 2500,
            'delivery_days' => 4,
            'packages' => [
                [
                    'name' => 'Базовый',
                    'description' => 'Минимальный пакет для проверки процесса.',
                    'price' => 2500,
                    'delivery_days' => 4,
                    'revisions_count' => 1,
                ],
            ],
        ];
    }
}
