<?php

namespace App\Http\Controllers\Performer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Task;
use App\Models\TaskFavorite;
use App\Models\TaskType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerformerFavoriteController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $status = $request->string('status')->toString() === 'closed' ? 'closed' : 'active';
        $user = $request->user();

        $taskFavorites = TaskFavorite::query()
            ->where('user_id', $user->id)
            ->with(['task.category', 'task.taskType.category', 'task.customer'])
            ->whereHas('task', function (Builder $query) use ($status): void {
                $status === 'active'
                    ? $query->where('status', Task::STATUS_PUBLISHED)
                    : $query->whereIn('status', [Task::STATUS_CLOSED, Task::STATUS_ARCHIVED]);
            })
            ->latest()
            ->get()
            ->map(fn (TaskFavorite $favorite): array => $this->taskPayload($favorite->task));

        $categories = $user->favoriteCategories()
            ->with('category.children')
            ->latest()
            ->get()
            ->map(fn ($favorite): array => $this->categoryPayload($favorite->category));

        $taskTypes = $user->favoriteTaskTypes()
            ->with('taskType.category')
            ->latest()
            ->get()
            ->map(fn ($favorite): array => $this->taskTypePayload($favorite->taskType));

        return Inertia::render('Performer/Favorites/Index', [
            'filters' => ['status' => $status],
            'tasks' => $taskFavorites,
            'categories' => $categories,
            'taskTypes' => $taskTypes,
        ]);
    }

    private function taskPayload(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'url' => $task->url,
            'status' => $task->status,
            'budget_label' => $this->budgetLabel($task),
            'deadline_at' => $task->deadline_at?->format('d.m.Y'),
            'offers_count' => $task->offers_count,
            'category' => $task->category?->name,
            'task_type' => $task->taskType?->name,
            'customer' => $task->customer?->name,
            'favorite_url' => route('tasks.favorite.destroy', $task),
        ];
    }

    private function categoryPayload(Category $category): array
    {
        $categoryIds = [$category->id, ...$category->children()->pluck('id')->all()];

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'task_count' => Task::published()->whereIn('category_id', $categoryIds)->count(),
            'tasks_url' => route('tasks', ['categories' => [$category->slug]]),
            'favorite_url' => route('categories.favorite.destroy', $category),
        ];
    }

    private function taskTypePayload(TaskType $taskType): array
    {
        return [
            'id' => $taskType->id,
            'name' => $taskType->name,
            'slug' => $taskType->slug,
            'category' => $taskType->category?->name,
            'task_count' => Task::published()->where('task_type_id', $taskType->id)->count(),
            'tasks_url' => route('tasks', ['task_types' => [$taskType->slug]]),
            'favorite_url' => route('task-types.favorite.destroy', $taskType),
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
