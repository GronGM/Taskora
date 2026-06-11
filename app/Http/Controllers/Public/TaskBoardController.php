<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TaskBoardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $this->filters($request);
        $activeCategory = $this->activeCategory($filters['category']);
        $activeTaskType = $this->activeTaskType($filters['type']);
        $favoriteCategoryIds = $this->favoriteCategoryIds($user);
        $favoriteCategoryFilterIds = $this->favoriteCategoryFilterIds($favoriteCategoryIds);
        $favoriteTaskTypeIds = $this->favoriteTaskTypeIds($user);
        $favoriteTaskIds = $this->favoriteTaskIds($user);

        $tasksQuery = Task::query()
            ->published()
            ->with(['category', 'taskType.category', 'customer'])
            ->when($activeCategory, fn (Builder $query) => $this->applyCategoryFilter($query, $activeCategory))
            ->when($activeTaskType, fn (Builder $query) => $query->where('task_type_id', $activeTaskType->id))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['budget_min'] !== null, function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query
                        ->where('budget_max', '>=', $filters['budget_min'])
                        ->orWhere('budget_min', '>=', $filters['budget_min']);
                });
            })
            ->when($filters['budget_max'] !== null, function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query
                        ->where('budget_min', '<=', $filters['budget_max'])
                        ->orWhere('budget_max', '<=', $filters['budget_max'])
                        ->orWhereNull('budget_min');
                });
            })
            ->when($filters['deadline_before'] !== '', fn (Builder $query) => $query->whereDate('deadline_at', '<=', $filters['deadline_before']))
            ->when($filters['without_offers'], fn (Builder $query) => $query->where('offers_count', 0))
            ->when($filters['urgent'], fn (Builder $query) => $query->whereNotNull('deadline_at')->whereDate('deadline_at', '<=', now()->addDays(3)->toDateString()))
            ->when($filters['favorite_categories'], fn (Builder $query) => $favoriteCategoryFilterIds->isEmpty()
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('category_id', $favoriteCategoryFilterIds))
            ->when($filters['favorite_types'], fn (Builder $query) => $favoriteTaskTypeIds->isEmpty()
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('task_type_id', $favoriteTaskTypeIds));

        $this->applySort($tasksQuery, $filters['sort']);

        $tasks = $tasksQuery
            ->get()
            ->map(fn (Task $task): array => $this->taskCard($task, $user, $favoriteTaskIds));

        return Inertia::render('Tasks/Index', [
            'categories' => $this->categories($user, $favoriteCategoryIds),
            'taskTypes' => $this->taskTypes($user, $favoriteTaskTypeIds),
            'popularTaskTypes' => $this->taskTypes($user, $favoriteTaskTypeIds)->sortByDesc('task_count')->take(10)->values(),
            'tasks' => $tasks,
            'filters' => $filters,
            'activeCategory' => $activeCategory ? $this->categoryPayload($activeCategory, $user, $favoriteCategoryIds) : null,
            'activeTaskType' => $activeTaskType ? $this->taskTypePayload($activeTaskType, $user, $favoriteTaskTypeIds) : null,
            'viewer' => [
                'role' => $user?->role,
                'is_performer' => $user?->isPerformer() === true,
            ],
            'favoritesSummary' => [
                'category_count' => $favoriteCategoryIds->count(),
                'task_type_count' => $favoriteTaskTypeIds->count(),
            ],
        ]);
    }

    public function show(Request $request, Task $task): Response
    {
        abort_unless($task->status === Task::STATUS_PUBLISHED, 404);

        $task->increment('views_count');
        $task->load(['category.parent', 'taskType.category', 'customer']);

        $user = $request->user();
        $existingOffer = $user?->isPerformer()
            ? $task->offers()->where('user_id', $user->id)->first()
            : null;
        $favoriteTaskIds = $this->favoriteTaskIds($user);

        return Inertia::render('Tasks/Show', [
            'task' => [
                ...$this->taskCard($task->refresh()->load(['category', 'taskType.category', 'customer']), $user, $favoriteTaskIds),
                'description' => $task->description,
                'views_count' => $task->views_count,
                'offer_url' => route('tasks.offers.store', $task),
            ],
            'canOffer' => $user?->isPerformer() === true && ! $existingOffer,
            'existingOffer' => $existingOffer ? [
                'status' => $existingOffer->status,
            ] : null,
            'offerStatusLabels' => [
                'submitted' => 'Отправлен',
                'withdrawn' => 'Отозван',
                'rejected' => 'Отклонен',
            ],
        ]);
    }

    private function filters(Request $request): array
    {
        $q = trim($request->string('q', $request->string('search')->toString())->toString());

        return [
            'q' => $q,
            'category' => $request->string('category')->toString(),
            'type' => $request->string('type')->toString(),
            'budget_min' => $request->filled('budget_min') ? max(0, (int) $request->input('budget_min')) : null,
            'budget_max' => $request->filled('budget_max') ? max(0, (int) $request->input('budget_max')) : null,
            'deadline_before' => $request->string('deadline_before')->toString(),
            'without_offers' => $request->boolean('without_offers'),
            'urgent' => $request->boolean('urgent'),
            'favorite_categories' => $request->boolean('favorite_categories'),
            'favorite_types' => $request->boolean('favorite_types'),
            'sort' => in_array($request->string('sort')->toString(), ['newest', 'urgent', 'budget_high', 'budget_low', 'offers_low'], true)
                ? $request->string('sort')->toString()
                : 'newest',
        ];
    }

    private function activeCategory(string $slug): ?Category
    {
        if ($slug === '') {
            return null;
        }

        return Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    private function activeTaskType(string $slug): ?TaskType
    {
        if ($slug === '') {
            return null;
        }

        return TaskType::query()
            ->active()
            ->where('slug', $slug)
            ->first();
    }

    private function applyCategoryFilter(Builder $query, Category $category): Builder
    {
        $categoryIds = [$category->id, ...$category->children()->pluck('id')->all()];

        return $query->whereIn('category_id', $categoryIds);
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'urgent' => $query->orderByRaw('deadline_at is null')->orderBy('deadline_at')->latest('created_at'),
            'budget_high' => $query->orderByRaw('COALESCE(budget_max, budget_min, 0) desc')->latest(),
            'budget_low' => $query->orderByRaw('COALESCE(budget_min, budget_max, 0) asc')->latest(),
            'offers_low' => $query->orderBy('offers_count')->latest(),
            default => $query->latest(),
        };
    }

    private function categories(?User $user, $favoriteCategoryIds)
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                ...$this->categoryPayload($category, $user, $favoriteCategoryIds),
                'children' => $category->children->map(fn (Category $child): array => $this->categoryPayload($child, $user, $favoriteCategoryIds))->values(),
            ]);
    }

    private function taskTypes(?User $user, $favoriteTaskTypeIds)
    {
        return TaskType::query()
            ->active()
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (TaskType $taskType): array => $this->taskTypePayload($taskType, $user, $favoriteTaskTypeIds));
    }

    private function categoryPayload(Category $category, ?User $user, $favoriteCategoryIds): array
    {
        $categoryIds = [$category->id, ...$category->children()->pluck('id')->all()];

        return [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'task_count' => Task::published()->whereIn('category_id', $categoryIds)->count(),
            'is_favorited' => $favoriteCategoryIds->contains($category->id),
            'can_favorite' => $user?->isPerformer() === true,
            'favorite_store_url' => route('categories.favorite.store', $category),
            'favorite_destroy_url' => route('categories.favorite.destroy', $category),
        ];
    }

    private function taskTypePayload(TaskType $taskType, ?User $user, $favoriteTaskTypeIds): array
    {
        return [
            'id' => $taskType->id,
            'category_id' => $taskType->category_id,
            'name' => $taskType->name,
            'slug' => $taskType->slug,
            'description' => $taskType->description,
            'category' => [
                'name' => $taskType->category?->name,
                'slug' => $taskType->category?->slug,
            ],
            'task_count' => Task::published()->where('task_type_id', $taskType->id)->count(),
            'is_favorited' => $favoriteTaskTypeIds->contains($taskType->id),
            'can_favorite' => $user?->isPerformer() === true,
            'favorite_store_url' => route('task-types.favorite.store', $taskType),
            'favorite_destroy_url' => route('task-types.favorite.destroy', $taskType),
        ];
    }

    private function taskCard(Task $task, ?User $user, $favoriteTaskIds): array
    {
        $deadline = $task->deadline_at ? Carbon::parse($task->deadline_at) : null;
        $isFavorited = $favoriteTaskIds->contains($task->id);

        return [
            'id' => $task->id,
            'title' => $task->title,
            'slug' => $task->slug,
            'url' => $task->url,
            'excerpt' => Str::limit($task->description, 170),
            'budget_label' => $this->budgetLabel($task),
            'deadline_at' => $deadline?->format('d.m.Y'),
            'deadline_iso' => $deadline?->toDateString(),
            'offers_count' => $task->offers_count,
            'published_at' => $task->created_at?->format('d.m.Y'),
            'category' => [
                'id' => $task->category?->id,
                'name' => $task->category?->name,
                'slug' => $task->category?->slug,
            ],
            'task_type' => $task->taskType ? [
                'id' => $task->taskType->id,
                'name' => $task->taskType->name,
                'slug' => $task->taskType->slug,
            ] : null,
            'customer' => [
                'name' => $task->customer?->name,
            ],
            'badges' => [
                'urgent' => $deadline !== null && $deadline->lte(now()->addDays(3)),
                'without_offers' => $task->offers_count === 0,
                'new' => $task->created_at?->gte(now()->subDays(3)) ?? false,
                'favorited' => $isFavorited,
            ],
            'favorite' => [
                'can_favorite' => $user?->isPerformer() === true,
                'show_login_cta' => $user === null,
                'is_favorited' => $isFavorited,
                'store_url' => route('tasks.favorite.store', $task),
                'destroy_url' => route('tasks.favorite.destroy', $task),
                'login_url' => route('login'),
            ],
        ];
    }

    private function favoriteTaskIds(?User $user)
    {
        if (! $user?->isPerformer()) {
            return collect();
        }

        return $user->taskFavorites()->pluck('task_id');
    }

    private function favoriteCategoryIds(?User $user)
    {
        if (! $user?->isPerformer()) {
            return collect();
        }

        return $user->favoriteCategories()->pluck('category_id');
    }

    private function favoriteCategoryFilterIds($favoriteCategoryIds)
    {
        if ($favoriteCategoryIds->isEmpty()) {
            return collect();
        }

        return Category::query()
            ->whereIn('id', $favoriteCategoryIds)
            ->with('children:id,parent_id')
            ->get()
            ->flatMap(fn (Category $category) => [
                $category->id,
                ...$category->children->pluck('id')->all(),
            ])
            ->unique()
            ->values();
    }

    private function favoriteTaskTypeIds(?User $user)
    {
        if (! $user?->isPerformer()) {
            return collect();
        }

        return $user->favoriteTaskTypes()->pluck('task_type_id');
    }

    private function budgetLabel(Task $task): string
    {
        if ($task->budget_min && $task->budget_max) {
            return number_format($task->budget_min, 0, ',', ' ').' - '.number_format($task->budget_max, 0, ',', ' ').' ₽';
        }

        if ($task->budget_min) {
            return 'от '.number_format($task->budget_min, 0, ',', ' ').' ₽';
        }

        if ($task->budget_max) {
            return 'до '.number_format($task->budget_max, 0, ',', ' ').' ₽';
        }

        return 'Бюджет обсуждается';
    }
}
