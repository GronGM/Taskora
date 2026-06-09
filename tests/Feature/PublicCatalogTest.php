<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_see_catalog(): void
    {
        $this->seed();

        $response = $this->get('/catalog')->assertOk();

        $this->assertSame('Catalog/Index', $response->inertiaPage()['component']);
        $this->assertTrue(
            collect($response->inertiaProps('services'))->pluck('title')->contains('Оформлю документ Word по требованиям'),
        );
    }

    public function test_guest_can_see_category_page(): void
    {
        $this->seed();

        $response = $this->get('/catalog/uchebnaya-pomoshch')->assertOk();

        $this->assertSame('Catalog/Category', $response->inertiaPage()['component']);
        $this->assertSame('Учебная помощь', $response->inertiaProps('category.name'));
        $this->assertTrue(
            collect($response->inertiaProps('services'))->pluck('title')->contains('Помогу структурировать курсовую работу'),
        );
    }

    public function test_guest_can_see_published_service_page(): void
    {
        $this->seed();

        $response = $this->get('/services/oformlyu-dokument-word-po-trebovaniyam')->assertOk();

        $this->assertSame('Services/Show', $response->inertiaPage()['component']);
        $this->assertSame('Оформлю документ Word по требованиям', $response->inertiaProps('service.title'));
        $this->assertTrue(
            collect($response->inertiaProps('service.packages'))->pluck('name')->contains('Базовый'),
        );
    }

    public function test_draft_service_is_not_publicly_visible(): void
    {
        $this->seed();

        $performer = User::where('email', 'performer@taskora.local')->firstOrFail();
        $category = Category::where('slug', 'dizayn')->firstOrFail();

        $draft = Service::factory()
            ->for($performer, 'user')
            ->for($category)
            ->draft()
            ->create([
                'title' => 'Черновик скрытой услуги',
                'slug' => 'chernovik-skrytoy-uslugi',
            ]);

        $response = $this->get('/catalog')->assertOk();

        $this->assertFalse(
            collect($response->inertiaProps('services'))->pluck('title')->contains($draft->title),
        );

        $this->get("/services/{$draft->slug}")
            ->assertNotFound();
    }

    public function test_performer_has_demo_services_after_seeder(): void
    {
        $this->seed();

        $performer = User::where('email', 'performer@taskora.local')->firstOrFail();

        $this->assertSame(5, $performer->services()->published()->count());
    }

    public function test_home_page_contains_database_categories(): void
    {
        $this->seed();

        $response = $this->get('/')->assertOk();

        $this->assertSame('Home', $response->inertiaPage()['component']);
        $this->assertTrue(
            collect($response->inertiaProps('categories'))->pluck('name')->contains('Учебная помощь'),
        );
        $this->assertTrue(
            collect($response->inertiaProps('categories'))->pluck('name')->contains('Сайты и разработка'),
        );
        $this->assertTrue(
            collect($response->inertiaProps('services'))->pluck('title')->contains('Оформлю документ Word по требованиям'),
        );
    }
}
