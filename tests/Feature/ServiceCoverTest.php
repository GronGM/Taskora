<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceCoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_performer_can_create_service_with_cover(): void
    {
        Storage::fake('public');
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();

        $this->actingAs($performer)
            ->post(route('performer.services.store'), [
                ...$this->servicePayload($category),
                'cover' => UploadedFile::fake()->image('cover.jpg', 1200, 600),
            ])
            ->assertRedirect();

        $service = Service::firstOrFail();
        $this->assertNotNull($service->cover_path);
        Storage::disk('public')->assertExists($service->cover_path);
        $this->assertNotNull($service->cover_url);
    }

    public function test_cover_must_be_an_image(): void
    {
        Storage::fake('public');
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();

        $this->actingAs($performer)
            ->post(route('performer.services.store'), [
                ...$this->servicePayload($category),
                'cover' => UploadedFile::fake()->create('cover.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('cover');

        $this->assertDatabaseCount('services', 0);
    }

    public function test_replacing_cover_returns_published_service_to_moderation_and_deletes_old_file(): void
    {
        Storage::fake('public');
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();

        $service = Service::factory()->for($performer, 'user')->create([
            'status' => Service::STATUS_PUBLISHED,
            'category_id' => $category->id,
        ]);
        $oldPath = UploadedFile::fake()->image('old.jpg')->store("services/{$service->id}", 'public');
        $service->update(['cover_path' => $oldPath]);

        $this->actingAs($performer)
            ->put(route('performer.services.update', $service), [
                ...$this->servicePayload($category, [
                    'category_id' => $service->category_id,
                    'title' => $service->title,
                    'short_description' => $service->short_description,
                    'description' => $service->description,
                ]),
                'cover' => UploadedFile::fake()->image('new.jpg', 1200, 600),
            ])
            ->assertRedirect();

        $service->refresh();
        $this->assertSame(Service::STATUS_PENDING_REVIEW, $service->status);
        $this->assertNotSame($oldPath, $service->cover_path);
        Storage::disk('public')->assertExists($service->cover_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_catalog_card_exposes_cover_url(): void
    {
        Storage::fake('public');
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $service = Service::factory()->for($performer, 'user')->create(['status' => Service::STATUS_PUBLISHED]);
        $service->update(['cover_path' => UploadedFile::fake()->image('c.jpg')->store("services/{$service->id}", 'public')]);

        $payload = collect($this->get('/catalog')->assertOk()->inertiaProps('services'))->firstWhere('id', $service->id);

        $this->assertNotNull($payload['cover_url']);
    }

    /**
     * @return array<string, mixed>
     */
    private function servicePayload(Category $category, array $overrides = []): array
    {
        return [
            'category_id' => $category->id,
            'title' => 'Услуга с обложкой',
            'short_description' => 'Короткое описание услуги для теста.',
            'description' => 'Полное описание услуги для теста обложек.',
            'price_from' => 1500,
            'delivery_days' => 3,
            'packages' => [
                ['name' => 'Базовый', 'description' => 'Пакет.', 'price' => 1500, 'delivery_days' => 3, 'revisions_count' => 1],
            ],
            'submit_for_review' => false,
            ...$overrides,
        ];
    }
}
