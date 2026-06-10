<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskBoardController extends Controller
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

        $tasks = Task::query()
            ->published()
            ->with(['category', 'customer'])
            ->when($activeCategory, fn (Builder $query) => $this->applyCategoryFilter($query, $activeCategory))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim($request->string('search')->toString());

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get()
            ->map(fn (Task $task): array => $this->taskCard($task));

        return Inertia::render('Tasks/Index', [
            'categories' => $this->categories(),
            'tasks' => $tasks,
            'filters' => [
                'category' => $activeCategory?->slug,
                'search' => $request->string('search')->toString(),
            ],
            'activeCategory' => $activeCategory ? $this->categoryPayload($activeCategory) : null,
        ]);
    }

    public function show(Request $request, Task $task): Response
    {
        abort_unless($task->status === Task::STATUS_PUBLISHED, 404);

        $task->increment('views_count');
        $task->load(['category.parent', 'customer']);

        $user = $request->user();
        $existingOffer = $user?->isPerformer()
            ? $task->offers()->where('user_id', $user->id)->first()
            : null;

        return Inertia::render('Tasks/Show', [
            'task' => [
                ...$this->taskCard($task->refresh()->load(['category', 'customer'])),
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
        ];
    }

    private function taskCard(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'slug' => $task->slug,
            'url' => $task->url,
            'budget_label' => $this->budgetLabel($task),
            'deadline_at' => $task->deadline_at?->format('d.m.Y'),
            'offers_count' => $task->offers_count,
            'category' => [
                'name' => $task->category->name,
                'slug' => $task->category->slug,
            ],
            'customer' => [
                'name' => $task->customer->name,
            ],
        ];
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
