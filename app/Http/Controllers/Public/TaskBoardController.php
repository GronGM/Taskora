<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use App\Services\Search\RelevanceSearch;
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
        $activeCategories = $this->activeCategories($filters['categories']);
        $activeTaskTypes = $this->activeTaskTypes($filters['task_types']);
        $selectedCategoryFilterIds = $this->selectedCategoryFilterIds($activeCategories);
        $selectedTaskTypeIds = $activeTaskTypes->pluck('id')->values();
        $favoriteCategoryIds = $this->favoriteCategoryIds($user);
        $favoriteCategoryFilterIds = $this->favoriteCategoryFilterIds($favoriteCategoryIds);
        $favoriteTaskTypeIds = $this->favoriteTaskTypeIds($user);
        $favoriteTaskIds = $this->favoriteTaskIds($user);
        $categoryPayloads = $this->categories($user, $favoriteCategoryIds);
        $taskTypePayloads = $this->taskTypes($user, $favoriteTaskTypeIds);

        $tasksQuery = Task::query()
            ->published()
            ->with(['category', 'taskType.category', 'customer'])
            ->when($selectedCategoryFilterIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('category_id', $selectedCategoryFilterIds))
            ->when($selectedTaskTypeIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('task_type_id', $selectedTaskTypeIds))
            ->when($filters['q'] !== '', fn (Builder $query) => app(RelevanceSearch::class)->apply($query, $filters['q']))
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
            ->when($selectedCategoryFilterIds->isEmpty() && $filters['favorite_categories'], fn (Builder $query) => $favoriteCategoryFilterIds->isEmpty()
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('category_id', $favoriteCategoryFilterIds))
            ->when($selectedTaskTypeIds->isEmpty() && $filters['favorite_types'], fn (Builder $query) => $favoriteTaskTypeIds->isEmpty()
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('task_type_id', $favoriteTaskTypeIds));

        $this->applySort($tasksQuery, $filters['sort']);

        $paginator = $tasksQuery->paginate(20)->withQueryString();

        $tasks = collect($paginator->items())
            ->map(fn (Task $task): array => $this->taskCard($task, $user, $favoriteTaskIds))
            ->values();

        return Inertia::render('Tasks/Index', [
            'categories' => $categoryPayloads,
            'taskTypes' => $taskTypePayloads,
            'popularTaskTypes' => $taskTypePayloads->sortByDesc('task_count')->take(10)->values(),
            'tasks' => $tasks,
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'next_page_url' => $paginator->nextPageUrl(),
            ],
            'filters' => $filters,
            'activeCategory' => $activeCategories->first() ? $this->categoryPayload($activeCategories->first(), $user, $favoriteCategoryIds) : null,
            'activeTaskType' => $activeTaskTypes->first() ? $this->taskTypePayload($activeTaskTypes->first(), $user, $favoriteTaskTypeIds) : null,
            'activeCategories' => $activeCategories->map(fn (Category $category): array => $this->categoryPayload($category, $user, $favoriteCategoryIds))->values(),
            'activeTaskTypes' => $activeTaskTypes->map(fn (TaskType $taskType): array => $this->taskTypePayload($taskType, $user, $favoriteTaskTypeIds))->values(),
            'viewer' => [
                'role' => $user?->role,
                'is_performer' => $user?->isPerformer() === true,
                'bulk_task_type_favorite_url' => $user?->isPerformer() === true
                    ? route('task-types.favorite.bulk')
                    : null,
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
            'canOffer' => $user?->isPerformer() === true
                && (! $existingOffer || $existingOffer->status === \App\Models\TaskOffer::STATUS_WITHDRAWN),
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
        $categories = $this->stringList($request->input('categories', []));
        $legacyCategory = trim($request->string('category')->toString());
        $taskTypes = $this->stringList($request->input('task_types', []));
        $legacyTaskType = trim($request->string('type')->toString());

        if ($legacyCategory !== '' && ! in_array($legacyCategory, $categories, true)) {
            $categories[] = $legacyCategory;
        }

        if ($legacyTaskType !== '' && ! in_array($legacyTaskType, $taskTypes, true)) {
            $taskTypes[] = $legacyTaskType;
        }

        return [
            'q' => $q,
            'category' => $categories[0] ?? '',
            'type' => $taskTypes[0] ?? '',
            'categories' => $categories,
            'task_types' => $taskTypes,
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

    private function stringList(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];

        return collect($items)
            ->flatten()
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function activeCategories(array $slugs)
    {
        if ($slugs === []) {
            return collect();
        }

        $order = array_flip($slugs);

        return Category::query()
            ->whereIn('slug', $slugs)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (Category $category): int => $order[$category->slug] ?? PHP_INT_MAX)
            ->values();
    }

    private function activeTaskTypes(array $slugs)
    {
        if ($slugs === []) {
            return collect();
        }

        $order = array_flip($slugs);

        return TaskType::query()
            ->active()
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->whereIn('slug', $slugs)
            ->get()
            ->sortBy(fn (TaskType $taskType): int => $order[$taskType->slug] ?? PHP_INT_MAX)
            ->values();
    }

    private function selectedCategoryFilterIds($categories)
    {
        if ($categories->isEmpty()) {
            return collect();
        }

        return $categories
            ->flatMap(fn (Category $category) => [
                $category->id,
                ...$category->children()->pluck('id')->all(),
            ])
            ->unique()
            ->values();
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

    /**
     * @var \Illuminate\Support\Collection<int, int>|null
     */
    private $categoryTaskCounts = null;

    /**
     * @var \Illuminate\Support\Collection<int, int>|null
     */
    private $taskTypeTaskCounts = null;

    private function categoryTaskCounts()
    {
        return $this->categoryTaskCounts ??= Task::published()
            ->selectRaw('category_id, count(*) as task_count')
            ->groupBy('category_id')
            ->pluck('task_count', 'category_id');
    }

    private function taskTypeTaskCounts()
    {
        return $this->taskTypeTaskCounts ??= Task::published()
            ->whereNotNull('task_type_id')
            ->selectRaw('task_type_id, count(*) as task_count')
            ->groupBy('task_type_id')
            ->pluck('task_count', 'task_type_id');
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
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->with('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (TaskType $taskType): array => $this->taskTypePayload($taskType, $user, $favoriteTaskTypeIds));
    }

    private function categoryPayload(Category $category, ?User $user, $favoriteCategoryIds): array
    {
        $childIds = $category->relationLoaded('children')
            ? $category->children->pluck('id')->all()
            : $category->children()->pluck('id')->all();
        $categoryIds = [$category->id, ...$childIds];
        $counts = $this->categoryTaskCounts();

        return [
            'id' => $category->id,
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'task_count' => (int) collect($categoryIds)->sum(fn (int $id): int => (int) ($counts[$id] ?? 0)),
            'tasks_url' => route('tasks', ['categories' => [$category->slug]]),
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
            'task_count' => (int) ($this->taskTypeTaskCounts()[$taskType->id] ?? 0),
            'tasks_url' => route('tasks', ['task_types' => [$taskType->slug]]),
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
            'budget_label' => $task->budget_label,
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

}
