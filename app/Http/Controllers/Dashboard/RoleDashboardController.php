<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleDashboardController extends Controller
{
    private const RECOMMENDED_TASKS_LIMIT = 5;

    public function customer(): Response
    {
        return Inertia::render('Dashboards/Customer');
    }

    public function performer(Request $request): Response
    {
        $user = $request->user();

        $favoriteCategoryIds = $this->favoriteCategoryIdsWithChildren($user);
        $favoriteTaskTypeIds = $user->favoriteTaskTypes()->pluck('task_type_id');
        $hasFavorites = $favoriteCategoryIds->isNotEmpty() || $favoriteTaskTypeIds->isNotEmpty();

        $tasks = Task::query()
            ->published()
            ->with(['category', 'taskType'])
            ->whereDoesntHave('offers', fn (Builder $query) => $query->where('user_id', $user->id))
            ->when($hasFavorites, function (Builder $query) use ($favoriteCategoryIds, $favoriteTaskTypeIds): void {
                $query->where(function (Builder $query) use ($favoriteCategoryIds, $favoriteTaskTypeIds): void {
                    $query
                        ->when($favoriteCategoryIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('category_id', $favoriteCategoryIds))
                        ->when($favoriteTaskTypeIds->isNotEmpty(), fn (Builder $query) => $query->orWhereIn('task_type_id', $favoriteTaskTypeIds));
                });
            })
            ->latest()
            ->limit(self::RECOMMENDED_TASKS_LIMIT)
            ->get()
            ->map(fn (Task $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'url' => $task->url,
                'budget_label' => $task->budget_label,
                'deadline_at' => $task->deadline_at?->format('d.m.Y'),
                'offers_count' => $task->offers_count,
                'category' => $task->category?->name,
                'task_type' => $task->taskType?->name,
                'published_at' => $task->created_at?->format('d.m.Y'),
            ]);

        return Inertia::render('Dashboards/Performer', [
            'recommendedTasks' => [
                'items' => $tasks,
                'has_favorites' => $hasFavorites,
                'board_url' => $hasFavorites ? route('tasks', ['favorite_categories' => 1, 'favorite_types' => 1]) : route('tasks'),
                'favorites_url' => route('performer.favorites.index'),
            ],
        ]);
    }

    public function moderator(): Response
    {
        return Inertia::render('Dashboards/Moderator');
    }

    public function admin(): Response
    {
        return Inertia::render('Dashboards/Admin');
    }

    private function favoriteCategoryIdsWithChildren(User $user)
    {
        $favoriteIds = $user->favoriteCategories()->pluck('category_id');

        if ($favoriteIds->isEmpty()) {
            return $favoriteIds;
        }

        return Category::query()
            ->whereIn('id', $favoriteIds)
            ->with('children:id,parent_id')
            ->get()
            ->flatMap(fn (Category $category) => [$category->id, ...$category->children->pluck('id')->all()])
            ->unique()
            ->values();
    }
}
