<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchRelevanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_search_is_case_insensitive_for_cyrillic(): void
    {
        $this->publishedService(['title' => 'Сделаю Презентацию с современным дизайном']);
        $this->publishedService(['title' => 'Настрою рекламу', 'description' => 'Про рекламные кабинеты.', 'short_description' => 'Реклама.']);

        $titles = collect($this->get('/catalog?search=презентацию')->assertOk()->inertiaProps('services'))->pluck('title');

        $this->assertTrue($titles->contains('Сделаю Презентацию с современным дизайном'));
        $this->assertFalse($titles->contains('Настрою рекламу'));
    }

    public function test_catalog_search_ranks_title_match_above_description_match(): void
    {
        $this->publishedService([
            'title' => 'Оформлю отчет по ГОСТ',
            'short_description' => 'Помогу с отчетом.',
            'description' => 'Сделаю красивую презентацию результатов работы.',
        ]);
        $this->publishedService([
            'title' => 'Сделаю презентацию под ключ',
            'short_description' => 'Слайды и дизайн.',
            'description' => 'Опыт больше пяти лет.',
        ]);

        $titles = collect($this->get('/catalog?search=презентацию')->assertOk()->inertiaProps('services'))->pluck('title');

        $this->assertSame('Сделаю презентацию под ключ', $titles->first());
        $this->assertTrue($titles->contains('Оформлю отчет по ГОСТ'));
    }

    public function test_catalog_search_requires_all_words(): void
    {
        $this->publishedService(['title' => 'Соберу таблицу Excel с расчетами']);
        $this->publishedService(['title' => 'Соберу таблицу учета', 'description' => 'Без формул.', 'short_description' => 'Таблица.']);

        $titles = collect($this->get('/catalog?search=таблицу+excel')->assertOk()->inertiaProps('services'))->pluck('title');

        $this->assertTrue($titles->contains('Соберу таблицу Excel с расчетами'));
        $this->assertFalse($titles->contains('Соберу таблицу учета'));
    }

    public function test_task_board_search_is_case_insensitive_for_cyrillic(): void
    {
        $this->publishedTask(['title' => 'Подготовить Презентацию на десять слайдов']);
        $this->publishedTask(['title' => 'Настроить сервер']);

        $titles = collect($this->get('/tasks?q=презентацию')->assertOk()->inertiaProps('tasks'))->pluck('title');

        $this->assertTrue($titles->contains('Подготовить Презентацию на десять слайдов'));
        $this->assertFalse($titles->contains('Настроить сервер'));
    }

    public function test_search_columns_are_updated_when_service_changes(): void
    {
        $service = $this->publishedService(['title' => 'Первое название']);

        $service->update(['title' => 'Совсем Новое Название']);

        $this->assertSame('совсем новое название', $service->refresh()->search_title);
    }

    public function test_service_page_shows_similar_services_from_same_category(): void
    {
        $category = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        $service = $this->publishedService(['category_id' => $category->id, 'title' => 'Основная услуга']);
        $similar = $this->publishedService(['category_id' => $category->id, 'title' => 'Похожая услуга']);
        $foreign = $this->publishedService(['category_id' => $otherCategory->id, 'title' => 'Чужая услуга']);
        $draft = Service::factory()->for($similar->user, 'user')->create([
            'category_id' => $category->id,
            'title' => 'Черновик услуги',
            'status' => Service::STATUS_DRAFT,
            'slug' => fake()->unique()->slug(4),
        ]);

        $payload = collect($this->get($service->url)->assertOk()->inertiaProps('similarServices'))->pluck('title');

        $this->assertTrue($payload->contains('Похожая услуга'));
        $this->assertFalse($payload->contains('Основная услуга'));
        $this->assertFalse($payload->contains('Чужая услуга'));
        $this->assertFalse($payload->contains('Черновик услуги'));
    }

    public function test_service_page_limits_similar_services_to_four(): void
    {
        $category = Category::factory()->create();
        $service = $this->publishedService(['category_id' => $category->id]);

        foreach (range(1, 6) as $i) {
            $this->publishedService(['category_id' => $category->id, 'title' => "Похожая услуга {$i}"]);
        }

        $this->assertCount(4, $this->get($service->url)->assertOk()->inertiaProps('similarServices'));
    }

    private function publishedService(array $overrides = []): Service
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        return Service::factory()->for($performer, 'user')->create([
            'status' => Service::STATUS_PUBLISHED,
            ...$overrides,
        ]);
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
