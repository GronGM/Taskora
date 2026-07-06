<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PerformerPortfolioItem;
use App\Models\PerformerProfile;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Services\Search\RelevanceSearch;
use App\Support\PerformerLevel;
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

        $sort = in_array($request->string('sort')->toString(), ['popular', 'newest', 'price_low', 'price_high', 'rating'], true)
            ? $request->string('sort')->toString()
            : 'popular';

        $servicesQuery = $this->publishedServicesQuery()
            ->when($activeCategory, fn (Builder $query) => $this->applyCategoryFilter($query, $activeCategory))
            ->when($request->filled('search'), fn (Builder $query) => app(RelevanceSearch::class)->apply($query, trim($request->string('search')->toString())))
            ->when($request->filled('price_min'), fn (Builder $query) => $query->where('price_from', '>=', max(0, (int) $request->input('price_min'))))
            ->when($request->filled('price_max'), fn (Builder $query) => $query->where('price_from', '<=', max(0, (int) $request->input('price_max'))));

        match ($sort) {
            'newest' => $servicesQuery->latest(),
            'price_low' => $servicesQuery->orderBy('price_from')->latest(),
            'price_high' => $servicesQuery->orderByDesc('price_from')->latest(),
            'rating' => $servicesQuery->orderByDesc('rating')->orderByDesc('reviews_count')->latest(),
            default => $servicesQuery->orderByDesc('is_featured')->orderByDesc('orders_count')->latest(),
        };

        $services = $servicesQuery
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Catalog/Index', [
            'categories' => $this->categories(),
            'services' => collect($services->items())->map(fn (Service $service): array => $this->serviceCard($service))->values(),
            'pagination' => $this->paginationPayload($services),
            'filters' => [
                'category' => $activeCategory?->slug,
                'search' => $request->string('search')->toString(),
                'price_min' => $request->string('price_min')->toString(),
                'price_max' => $request->string('price_max')->toString(),
                'sort' => $sort,
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
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Catalog/Category', [
            'pagination' => $this->paginationPayload($services),
            'category' => $this->categoryPayload($category),
            'children' => $category->children()
                ->where('is_active', true)
                ->get()
                ->map(fn (Category $category): array => $this->categoryPayload($category)),
            'services' => collect($services->items())->map(fn (Service $service): array => $this->serviceCard($service))->values(),
        ]);
    }

    public function service(Service $service): Response
    {
        abort_unless($service->status === Service::STATUS_PUBLISHED, 404);

        $service->load([
            'category.parent',
            'user.performerProfile',
            'packages',
            'reviews' => fn ($query) => $query
                ->where('status', Review::STATUS_PUBLISHED)
                ->where('is_public', true)
                ->with('customer')
                ->latest('published_at')
                ->latest()
                ->limit(5),
        ]);

        $similarServices = $this->publishedServicesQuery()
            ->whereKeyNot($service->id)
            ->where('category_id', $service->category_id)
            ->orderByDesc('rating')
            ->orderByDesc('orders_count')
            ->latest()
            ->limit(4)
            ->get()
            ->map(fn (Service $similar): array => $this->serviceCard($similar));

        return Inertia::render('Services/Show', [
            'similarServices' => $similarServices,
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

    public function performers(Request $request): Response
    {
        $activeCategory = null;

        if ($request->filled('category')) {
            $activeCategory = Category::query()
                ->where('slug', $request->string('category')->toString())
                ->where('is_active', true)
                ->first();
        }

        $performers = User::query()
            ->where('role', User::ROLE_PERFORMER)
            ->where(function (Builder $query): void {
                $query
                    ->whereHas('services', fn (Builder $query) => $query->published())
                    ->orWhereHas('performerProfile', fn (Builder $query) => $query->where('is_public', true));
            })
            ->when($activeCategory, function (Builder $query) use ($activeCategory): void {
                $categoryIds = [$activeCategory->id, ...$activeCategory->children()->pluck('id')->all()];

                $query->where(function (Builder $query) use ($categoryIds): void {
                    $query
                        ->whereHas('performerProfile.specializations', fn (Builder $query) => $query->whereIn('categories.id', $categoryIds))
                        ->orWhereHas('services', fn (Builder $query) => $query->published()->whereIn('category_id', $categoryIds));
                });
            })
            ->with(['performerProfile.specializations.parent'])
            ->withCount(['services as published_services_count' => fn (Builder $query) => $query->published()])
            ->orderByDesc('performer_completed_orders_count')
            ->orderByDesc('performer_reviews_count')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Performers/Index', [
            'performers' => collect($performers->items())->map(fn (User $user): array => $this->performerCard($user))->values(),
            'pagination' => $this->paginationPayload($performers),
            'categories' => $this->categories(),
            'filters' => [
                'category' => $activeCategory?->slug,
            ],
        ]);
    }

    public function performer(User $user): Response
    {
        abort_unless($user->isPerformer(), 404);

        $user->load([
            'performerProfile.specializations.parent',
            'performerProfile.publishedPortfolioItems.category',
        ]);
        $user->loadCount(['services as published_services_count' => fn (Builder $query) => $query->published()]);

        $services = $user->services()
            ->published()
            ->with(['category', 'user.performerProfile'])
            ->orderByDesc('is_featured')
            ->orderByDesc('orders_count')
            ->get()
            ->map(fn (Service $service): array => $this->serviceCard($service));

        $reviews = $user->receivedReviews()
            ->where('status', Review::STATUS_PUBLISHED)
            ->where('is_public', true)
            ->with(['customer', 'service', 'task', 'order'])
            ->latest('published_at')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Review $review): array => $this->reviewPayload($review));

        $profile = $user->performerProfile?->is_public ? $user->performerProfile : null;

        return Inertia::render('Performers/Show', [
            'performer' => [
                ...$this->performerCard($user),
                'bio' => $profile?->bio,
                'portfolio_summary' => $profile?->portfolio_summary,
                'cover_url' => $profile?->cover_url,
                'response_time_label' => $profile?->response_time_label,
                'trust_badges' => $this->trustBadges($user, $profile),
            ],
            'services' => $services,
            'portfolio' => $profile
                ? $profile->publishedPortfolioItems->map(fn (PerformerPortfolioItem $item): array => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'category' => $item->category?->name,
                    'image_url' => $item->image_url,
                    'file_url' => $item->file_url,
                    'external_url' => $item->external_url,
                ])
                : [],
            'reviews' => $reviews,
        ]);
    }

    public function performerReviews(User $user): Response
    {
        return $this->performer($user);
    }

    /**
     * @return array<string, mixed>
     */
    private function paginationPayload($paginator): array
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'next_page_url' => $paginator->nextPageUrl(),
        ];
    }

    private function publishedServicesQuery(): Builder
    {
        return Service::query()
            ->published()
            ->with(['category', 'user.performerProfile']);
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
        $service->loadMissing(['category', 'user.performerProfile']);
        $profile = $service->user->performerProfile?->is_public ? $service->user->performerProfile : null;

        return [
            'id' => $service->id,
            'title' => $service->title,
            'slug' => $service->slug,
            'short_description' => $service->short_description,
            'cover_url' => $service->cover_url,
            'price_from' => $service->price_from,
            'delivery_days' => $service->delivery_days,
            'max_review_hold_days' => min($service->max_review_hold_days ?? \App\Models\Order::REVIEW_HOLD_MAX_DAYS, \App\Models\Order::REVIEW_HOLD_MAX_DAYS),
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
                'name' => $profile?->display_name ?: $service->user->name,
                'avatar_url' => $profile?->avatar_url,
                'headline' => $profile?->headline,
                'is_verified' => $profile?->verification_status === PerformerProfile::STATUS_VERIFIED,
                'level' => $service->user->performer_level ?? PerformerLevel::NOVICE,
                'level_label' => PerformerLevel::label($service->user->performer_level),
                'rating' => $service->user->performer_reviews_count > 0 ? (float) $service->user->performer_rating : null,
                'reviews_count' => $service->user->performer_reviews_count,
                'completed_orders_count' => $service->user->performer_completed_orders_count,
                'reviews_url' => route('performers.reviews', $service->user),
                'profile_url' => route('performers.show', $service->user),
            ],
        ];
    }

    private function performerCard(User $user): array
    {
        $profile = $user->performerProfile?->is_public ? $user->performerProfile : null;

        return [
            'id' => $user->id,
            'name' => $profile?->display_name ?: $user->name,
            'role' => 'Исполнитель',
            'level' => $user->performer_level ?? PerformerLevel::NOVICE,
            'level_label' => PerformerLevel::label($user->performer_level),
            'headline' => $profile?->headline,
            'avatar_url' => $profile?->avatar_url,
            'is_verified' => $profile?->verification_status === PerformerProfile::STATUS_VERIFIED,
            'services_count' => (int) ($user->published_services_count ?? 0),
            'rating' => $user->performer_reviews_count > 0 ? (float) $user->performer_rating : null,
            'reviews_count' => $user->performer_reviews_count,
            'completed_orders_count' => $user->performer_completed_orders_count,
            'specializations' => $profile
                ? $profile->specializations->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'url' => route('performers', ['category' => $category->slug]),
                ])
                : [],
            'reviews_url' => route('performers.reviews', $user),
            'profile_url' => route('performers.show', $user),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function trustBadges(User $user, ?PerformerProfile $profile): array
    {
        $badges = [];

        $badges[] = [
            'label' => PerformerLevel::label($user->performer_level),
            'tone' => match ($user->performer_level) {
                PerformerLevel::EXPERT => 'emerald',
                PerformerLevel::PRO => 'amber',
                PerformerLevel::SPECIALIST => 'blue',
                default => 'slate',
            },
        ];

        if ($profile?->verification_status === PerformerProfile::STATUS_VERIFIED) {
            $badges[] = ['label' => 'Проверен', 'tone' => 'emerald'];
        }

        if ($user->performer_reviews_count > 0) {
            $badges[] = ['label' => 'Есть отзывы', 'tone' => 'blue'];
        }

        if ($user->performer_completed_orders_count > 0) {
            $badges[] = ['label' => 'Выполненные заказы', 'tone' => 'slate'];
        }

        if (filled($profile?->response_time_label)) {
            $badges[] = ['label' => $profile->response_time_label, 'tone' => 'amber'];
        }

        return $badges;
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
