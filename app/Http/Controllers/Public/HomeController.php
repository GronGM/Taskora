<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'url' => $category->catalog_url,
            ]);

        $services = Service::query()
            ->published()
            ->with(['category', 'user'])
            ->orderByDesc('is_featured')
            ->orderByDesc('orders_count')
            ->limit(3)
            ->get()
            ->map(fn (Service $service): array => $this->serviceCard($service));

        return Inertia::render('Home', [
            'categories' => $categories,
            'services' => $services,
        ]);
    }

    private function serviceCard(Service $service): array
    {
        return [
            'id' => $service->id,
            'title' => $service->title,
            'slug' => $service->slug,
            'short_description' => $service->short_description,
            'price_from' => $service->price_from,
            'delivery_days' => $service->delivery_days,
            'rating' => $service->reviews_count > 0 ? (float) $service->rating : null,
            'reviews_count' => $service->reviews_count,
            'orders_count' => $service->orders_count,
            'url' => $service->url,
            'category' => [
                'name' => $service->category->name,
                'slug' => $service->category->slug,
                'url' => $service->category->catalog_url,
            ],
            'performer' => [
                'id' => $service->user->id,
                'name' => $service->user->name,
                'rating' => $service->user->performer_reviews_count > 0 ? (float) $service->user->performer_rating : null,
                'reviews_count' => $service->user->performer_reviews_count,
                'completed_orders_count' => $service->user->performer_completed_orders_count,
                'reviews_url' => route('performers.reviews', $service->user),
            ],
        ];
    }
}
