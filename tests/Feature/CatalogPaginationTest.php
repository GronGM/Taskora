<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_is_paginated_by_twenty_four(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        Service::factory()->count(30)->for($performer, 'user')->create(['status' => Service::STATUS_PUBLISHED]);

        $firstPage = $this->get('/catalog')->assertOk();
        $this->assertCount(24, $firstPage->inertiaProps('services'));

        $pagination = $firstPage->inertiaProps('pagination');
        $this->assertSame(30, $pagination['total']);
        $this->assertSame(2, $pagination['last_page']);

        $this->assertCount(6, $this->get('/catalog?page=2')->assertOk()->inertiaProps('services'));
    }

    public function test_catalog_pagination_keeps_search_filter(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        foreach (range(1, 25) as $i) {
            Service::factory()->for($performer, 'user')->create([
                'status' => Service::STATUS_PUBLISHED,
                'title' => "Уникальная презентация {$i}",
            ]);
        }

        $pagination = $this->get('/catalog?search=презентация')->assertOk()->inertiaProps('pagination');

        $this->assertSame(25, $pagination['total']);
        $this->assertStringContainsString('page=2', $pagination['next_page_url']);
    }

    public function test_category_page_is_paginated(): void
    {
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
        $category = Category::factory()->create();

        Service::factory()->count(26)->for($performer, 'user')->create([
            'status' => Service::STATUS_PUBLISHED,
            'category_id' => $category->id,
        ]);

        $response = $this->get(route('catalog.category', $category))->assertOk();

        $this->assertCount(24, $response->inertiaProps('services'));
        $this->assertSame(26, $response->inertiaProps('pagination')['total']);
    }

    public function test_performers_page_is_paginated(): void
    {
        foreach (range(1, 25) as $i) {
            $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);
            Service::factory()->for($performer, 'user')->create(['status' => Service::STATUS_PUBLISHED]);
        }

        $response = $this->get('/performers')->assertOk();

        $this->assertCount(24, $response->inertiaProps('performers'));
        $this->assertSame(25, $response->inertiaProps('pagination')['total']);
    }
}
