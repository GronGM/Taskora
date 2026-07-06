<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;
use App\Services\Payments\PaymentLedgerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleDashboardController extends Controller
{
    private const RECOMMENDED_TASKS_LIMIT = 5;

    public function customer(Request $request): Response
    {
        $user = $request->user();

        $ordersByStatus = $user->customerOrders()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $needsReview = $user->customerOrders()
            ->where('status', Order::STATUS_SUBMITTED_FOR_REVIEW)
            ->latest('submitted_at')
            ->limit(5)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'title' => $order->title,
                'url' => route('customer.orders.show', $order),
                'review_hold_until' => $order->review_hold_until?->format('d.m.Y'),
            ]);

        $tasksWithOffers = $user->tasks()
            ->where('status', Task::STATUS_PUBLISHED)
            ->where('offers_count', '>', 0)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Task $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'url' => route('customer.tasks.show', $task),
                'offers_count' => $task->offers_count,
            ]);

        return Inertia::render('Dashboards/Customer', [
            'onboarding' => [
                'has_tasks' => $user->tasks()->exists(),
                'has_orders' => $user->customerOrders()->exists(),
            ],
            'stats' => [
                'orders_in_progress' => (int) ($ordersByStatus[Order::STATUS_IN_PROGRESS] ?? 0)
                    + (int) ($ordersByStatus[Order::STATUS_REVISION_REQUESTED] ?? 0),
                'orders_awaiting_payment' => (int) ($ordersByStatus[Order::STATUS_AWAITING_PAYMENT] ?? 0),
                'orders_to_review' => (int) ($ordersByStatus[Order::STATUS_SUBMITTED_FOR_REVIEW] ?? 0),
                'orders_completed' => (int) ($ordersByStatus[Order::STATUS_COMPLETED] ?? 0),
                'published_tasks' => $user->tasks()->where('status', Task::STATUS_PUBLISHED)->count(),
                'pending_offers' => (int) TaskOffer::query()
                    ->where('status', TaskOffer::STATUS_SUBMITTED)
                    ->whereHas('task', fn (Builder $query) => $query
                        ->where('user_id', $user->id)
                        ->where('status', Task::STATUS_PUBLISHED))
                    ->count(),
            ],
            'attention' => [
                'needs_review' => $needsReview,
                'tasks_with_offers' => $tasksWithOffers,
            ],
        ]);
    }

    public function performer(Request $request, PaymentLedgerService $ledger): Response
    {
        $user = $request->user();

        $ordersByStatus = $user->performerOrders()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $finance = $ledger->getPerformerFinanceSummary($user);

        $needsRevision = $user->performerOrders()
            ->where('status', Order::STATUS_REVISION_REQUESTED)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'title' => $order->title,
                'url' => route('performer.orders.show', $order),
            ]);

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
            'stats' => [
                'orders_in_progress' => (int) ($ordersByStatus[Order::STATUS_IN_PROGRESS] ?? 0),
                'orders_on_review' => (int) ($ordersByStatus[Order::STATUS_SUBMITTED_FOR_REVIEW] ?? 0),
                'orders_revision' => (int) ($ordersByStatus[Order::STATUS_REVISION_REQUESTED] ?? 0),
                'orders_completed' => (int) ($ordersByStatus[Order::STATUS_COMPLETED] ?? 0),
                'active_offers' => $user->taskOffers()->where('status', TaskOffer::STATUS_SUBMITTED)->count(),
                'pending_amount' => $finance['pending_amount'],
                'available_amount' => $finance['available_amount'],
            ],
            'attention' => [
                'needs_revision' => $needsRevision,
            ],
            'onboarding' => [
                'has_profile' => $user->performerProfile()->exists(),
                'has_services' => $user->services()->exists(),
                'has_offers' => $user->taskOffers()->exists(),
            ],
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
