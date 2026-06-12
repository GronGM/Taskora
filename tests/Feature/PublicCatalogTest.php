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

    public function test_only_published_services_are_publicly_visible(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();

        $published = Service::factory()->for($performer, 'user')->for($category)->create([
            'title' => 'Публичная услуга',
            'slug' => 'publichnaya-usluga',
            'status' => Service::STATUS_PUBLISHED,
        ]);

        foreach ([
            Service::STATUS_DRAFT => 'Черновик услуги',
            Service::STATUS_PENDING_REVIEW => 'Услуга на проверке',
            Service::STATUS_REJECTED => 'Отклоненная услуга',
            Service::STATUS_ARCHIVED => 'Архивная услуга',
        ] as $status => $title) {
            Service::factory()->for($performer, 'user')->for($category)->create([
                'title' => $title,
                'status' => $status,
            ]);
        }

        $titles = collect($this->get('/catalog')->assertOk()->inertiaProps('services'))->pluck('title');

        $this->assertTrue($titles->contains($published->title));
        $this->assertFalse($titles->contains('Черновик услуги'));
        $this->assertFalse($titles->contains('Услуга на проверке'));
        $this->assertFalse($titles->contains('Отклоненная услуга'));
        $this->assertFalse($titles->contains('Архивная услуга'));
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

    public function test_home_page_uses_polished_hero_copy_and_ctas(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Home.jsx'));

        $this->assertStringContainsString('Найдите исполнителя для задачи — быстро, понятно и безопасно', $source);
        $this->assertStringContainsString('Разместите задание или выберите готовую услугу.', $source);
        $this->assertStringContainsString('Разместить задание', $source);
        $this->assertStringContainsString('Найти услугу', $source);
        $this->assertStringContainsString('Стать исполнителем', $source);
        $this->assertStringContainsString('px-4 py-10 sm:px-6 sm:py-14', $source);
        $this->assertStringContainsString('text-3xl font-semibold leading-tight', $source);
        $this->assertStringContainsString('mt-6 flex flex-col gap-2 sm:mt-8', $source);
        $this->assertStringContainsString('sm:py-3', $source);
        $this->assertStringContainsString("import { MessagesSquare, Search, ShieldCheck, Star } from 'lucide-react';", $source);
        $this->assertStringNotContainsString('Премиальная основа для сервиса', $source);
    }

    public function test_category_links_have_dark_hover_and_focus_readability_classes(): void
    {
        $catalogIndex = file_get_contents(resource_path('js/Pages/Catalog/Index.jsx'));
        $categoryPage = file_get_contents(resource_path('js/Pages/Catalog/Category.jsx'));
        $tasksIndex = file_get_contents(resource_path('js/Pages/Tasks/Index.jsx'));
        $serviceCard = file_get_contents(resource_path('js/Components/ServiceCard.jsx'));

        foreach ([$catalogIndex, $categoryPage, $tasksIndex, $serviceCard] as $source) {
            $this->assertStringContainsString('dark:hover:bg-', $source);
            $this->assertStringContainsString('dark:hover:text-', $source);
            $this->assertStringContainsString('focus-visible:ring', $source);
        }

        $this->assertStringContainsString('dark:hover:bg-slate-800', $catalogIndex);
        $this->assertStringContainsString('dark:hover:bg-slate-800', $categoryPage);
        $this->assertStringContainsString('dark:hover:bg-slate-800', $tasksIndex);
        $this->assertStringContainsString('dark:hover:bg-blue-900', $serviceCard);
    }
}
