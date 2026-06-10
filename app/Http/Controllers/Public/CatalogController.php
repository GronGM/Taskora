<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $activeCategory = null;

        if ($request->filled('category')) {
            $activeCategory = Category::query()
                ->where('slug', $request->string('category')->toString())
                ->where('is_active', true)
                ->first();
        }

        $services = $this->publishedServicesQuery()
            ->when($activeCategory, fn (Builder $query) => $this->applyCategoryFilter($query, $activeCategory))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim($request->string('search')->toString());

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_featured')
            ->orderByDesc('orders_count')
            ->get()
            ->map(fn (Service $service): array => $this->serviceCard($service));

        return Inertia::render('Catalog/Index', [
            'categories' => $this->categories(),
            'services' => $services,
            'filters' => [
                'category' => $activeCategory?->slug,
                'search' => $request->string('search')->toString(),
            ],
            'activeCategory' => $activeCategory ? $this->categoryPayload($activeCategory) : null,
        ]);
    }

    public function category(Category $category): Response
    {
        abort_unless($category->is_active, 404);

        $servicesQuery = $this->publishedServicesQuery();
        $this->applyCategoryFilter($servicesQuery, $category);

        $services = $servicesQuery
            ->orderByDesc('is_featured')
            ->orderByDesc('orders_count')
            ->get()
            ->map(fn (Service $service): array => $this->serviceCard($service));

        return Inertia::render('Catalog/Category', [
            'category' => $this->categoryPayload($category),
            'children' => $category->children()
                ->where('is_active', true)
                ->get()
                ->map(fn (Category $category): array => $this->categoryPayload($category)),
            'services' => $services,
        ]);
    }

    public function service(Service $service): Response
    {
        abort_unless($service->status === Service::STATUS_PUBLISHED, 404);

        $service->load([
            'category.parent',
            'user',
            'packages',
            'reviews' => fn ($query) => $query
                ->where('status', Review::STATUS_PUBLISHED)
                ->where('is_public', true)
                ->with('customer')
                ->latest('published_at')
                ->latest()
                ->limit(5),
        ]);

        return Inertia::render('Services/Show', [
            'service' => [
                ...$this->serviceCard($service),
                'description' => $service->description,
                'orders_count' => $service->orders_count,
                'is_featured' => $service->is_featured,
                'packages' => $service->packages->map(fn ($package): array => [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'price' => $package->price,
                    'delivery_days' => $package->delivery_days,
                    'revisions_count' => $package->revisions_count,
                ]),
                'reviews' => $service->reviews->map(fn (Review $review): array => $this->reviewPayload($review)),
                'order_url' => route('services.order.store', $service),
            ],
        ]);
    }

    public function performers(): Response
    {
        $performers = User::query()
            ->where('role', User::ROLE_PERFORMER)
            ->whereHas('services', fn (Builder $query) => $query->published())
            ->withCount(['services as published_services_count' => fn (Builder $query) => $query->published()])
            ->orderByDesc('performer_completed_orders_count')
            ->orderByDesc('performer_reviews_count')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => 'Исполнитель',
                'services_count' => $user->published_services_count,
                'rating' => $user->performer_reviews_count > 0 ? (float) $user->performer_rating : null,
                'reviews_count' => $user->performer_reviews_count,
                'completed_orders_count' => $user->performer_completed_orders_count,
                'reviews_url' => route('performers.reviews', $user),
            ]);

        return Inertia::render('Performers/Index', [
            'performers' => $performers,
        ]);
    }

    public function performerReviews(User $user): Response
    {
        abort_unless($user->isPerformer(), 404);

        $user->loadCount(['services as published_services_count' => fn (Builder $query) => $query->published()]);

        $reviews = $user->receivedReviews()
            ->where('status', Review::STATUS_PUBLISHED)
            ->where('is_public', true)
            ->with(['customer', 'service', 'task', 'order'])
            ->latest('published_at')
            ->latest()
            ->get()
            ->map(fn (Review $review): array => $this->reviewPayload($review));

        return Inertia::render('Performers/Reviews', [
            'performer' => [
                'id' => $user->id,
                'name' => $user->name,
                'rating' => $user->performer_reviews_count > 0 ? (float) $user->performer_rating : null,
                'reviews_count' => $user->performer_reviews_count,
                'completed_orders_count' => $user->performer_completed_orders_count,
                'services_count' => $user->published_services_count,
            ],
            'reviews' => $reviews,
        ]);
    }

    private function publishedServicesQuery(): Builder
    {
        return Service::query()
            ->published()
            ->with(['category', 'user']);
    }

    private function categories()
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                ...$this->categoryPayload($category),
                'children' => $category->children->map(fn (Category $child): array => $this->categoryPayload($child)),
            ]);
    }

    private function applyCategoryFilter(Builder $query, Category $category): Builder
    {
        $categoryIds = [$category->id, ...$category->children()->pluck('id')->all()];

        return $query->whereIn('category_id', $categoryIds);
    }

    private function categoryPayload(Category $category): array
    {
        return [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'icon' => $category->icon,
            'url' => $category->catalog_url,
        ];
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

    private function reviewPayload(Review $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'published_at' => $review->published_at?->format('d.m.Y'),
            'customer' => [
                'name' => $review->customer?->name ?? 'Заказчик Таскоры',
            ],
            'source' => [
                'title' => $review->service?->title ?? $review->task?->title ?? $review->order?->title,
                'url' => $review->service?->url,
            ],
        ];
    }
}
