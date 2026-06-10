<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreTaskRequest;
use App\Http\Requests\Customer\UpdateTaskRequest;
use App\Models\Category;
use App\Models\Task;
use App\Models\TaskOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CustomerTaskController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Task::class);

        $tasks = request()->user()
            ->tasks()
            ->with('category')
            ->latest()
            ->get()
            ->map(fn (Task $task): array => $this->taskListPayload($task));

        return Inertia::render('Customer/Tasks/Index', [
            'tasks' => $tasks,
            'statusLabels' => $this->taskStatusLabels(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Task::class);

        return Inertia::render('Customer/Tasks/Create', [
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $task = Task::create([
            ...$request->taskData(),
            'user_id' => $request->user()->id,
            'slug' => $this->uniqueSlug($request->string('title')->toString()),
            'status' => $request->boolean('publish') ? Task::STATUS_PUBLISHED : Task::STATUS_DRAFT,
        ]);

        return redirect()
            ->route('customer.tasks.show', $task)
            ->with('success', $task->status === Task::STATUS_PUBLISHED
                ? 'Задание опубликовано.'
                : 'Черновик задания сохранен.');
    }

    public function show(Task $task): Response
    {
        Gate::authorize('view', $task);

        $task->load(['category', 'customer', 'offers.performer']);

        return Inertia::render('Customer/Tasks/Show', [
            'task' => $this->taskDetailPayload($task),
            'statusLabels' => $this->taskStatusLabels(),
            'offerStatusLabels' => $this->offerStatusLabels(),
        ]);
    }

    public function edit(Task $task): Response
    {
        Gate::authorize('update', $task);

        $task->load('category');

        return Inertia::render('Customer/Tasks/Edit', [
            'task' => $this->taskFormPayload($task),
            'categories' => $this->categoryOptions(),
            'statusLabels' => $this->taskStatusLabels(),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $data = $request->taskData();

        if ($task->title !== $data['title']) {
            $data['slug'] = $this->uniqueSlug($data['title'], $task);
        }

        $task->update($data);

        return redirect()
            ->route('customer.tasks.show', $task)
            ->with('success', 'Задание обновлено.');
    }

    public function publish(Task $task): RedirectResponse
    {
        Gate::authorize('publish', $task);

        $task->update(['status' => Task::STATUS_PUBLISHED]);

        return redirect()
            ->route('customer.tasks.show', $task)
            ->with('success', 'Задание опубликовано.');
    }

    public function archive(Task $task): RedirectResponse
    {
        Gate::authorize('archive', $task);

        $task->update(['status' => Task::STATUS_ARCHIVED]);

        return redirect()
            ->route('customer.tasks.index')
            ->with('success', 'Задание перенесено в архив.');
    }

    private function uniqueSlug(string $title, ?Task $except = null): string
    {
        $base = Str::slug($title, '-', 'ru') ?: 'task';
        $slug = $base;
        $counter = 2;

        while (Task::query()
            ->where('slug', $slug)
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function taskListPayload(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status,
            'category' => $task->category?->name,
            'budget_label' => $this->budgetLabel($task),
            'deadline_at' => $task->deadline_at?->format('d.m.Y'),
            'offers_count' => $task->offers_count,
            'show_url' => route('customer.tasks.show', $task),
            'edit_url' => route('customer.tasks.edit', $task),
            'publish_url' => route('customer.tasks.publish', $task),
            'archive_url' => route('customer.tasks.archive', $task),
            'public_url' => $task->status === Task::STATUS_PUBLISHED ? $task->url : null,
        ];
    }

    private function taskDetailPayload(Task $task): array
    {
        return [
            ...$this->taskFormPayload($task),
            'status' => $task->status,
            'category' => $task->category?->name,
            'budget_label' => $this->budgetLabel($task),
            'deadline_label' => $task->deadline_at?->format('d.m.Y'),
            'offers_count' => $task->offers_count,
            'views_count' => $task->views_count,
            'edit_url' => route('customer.tasks.edit', $task),
            'publish_url' => route('customer.tasks.publish', $task),
            'archive_url' => route('customer.tasks.archive', $task),
            'public_url' => $task->status === Task::STATUS_PUBLISHED ? $task->url : null,
            'offers' => $task->offers
                ->sortByDesc('created_at')
                ->map(fn (TaskOffer $offer): array => [
                    'id' => $offer->id,
                    'message' => $offer->message,
                    'price' => $offer->price,
                    'delivery_days' => $offer->delivery_days,
                    'status' => $offer->status,
                    'created_at' => $offer->created_at?->format('d.m.Y H:i'),
                    'performer' => [
                        'name' => $offer->performer?->name,
                    ],
                    'accept_url' => route('customer.task-offers.accept', $offer),
                    'reject_url' => route('customer.task-offers.reject', $offer),
                ])
                ->values(),
        ];
    }

    private function taskFormPayload(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'category_id' => $task->category_id,
            'budget_min' => $task->budget_min,
            'budget_max' => $task->budget_max,
            'deadline_at' => $task->deadline_at?->format('Y-m-d'),
            'status' => $task->status,
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

    private function categoryOptions()
    {
        return Category::query()
            ->where('is_active', true)
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->parent ? "{$category->parent->name} / {$category->name}" : $category->name,
            ]);
    }

    /**
     * @return array<string, string>
     */
    private function taskStatusLabels(): array
    {
        return [
            Task::STATUS_DRAFT => 'Черновик',
            Task::STATUS_PUBLISHED => 'Опубликовано',
            Task::STATUS_CLOSED => 'Закрыто',
            Task::STATUS_ARCHIVED => 'В архиве',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function offerStatusLabels(): array
    {
        return [
            TaskOffer::STATUS_SUBMITTED => 'Отправлен',
            TaskOffer::STATUS_WITHDRAWN => 'Отозван',
            TaskOffer::STATUS_REJECTED => 'Отклонен',
            TaskOffer::STATUS_ACCEPTED => 'Принят',
        ];
    }
}
