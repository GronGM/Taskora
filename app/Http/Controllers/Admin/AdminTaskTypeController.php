<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaskTypeRequest;
use App\Http\Requests\Admin\UpdateTaskTypeRequest;
use App\Models\Category;
use App\Models\TaskType;
use App\Support\TaskoraSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTaskTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $taskTypes = TaskType::query()
            ->with('category')
            ->withCount(['tasks', 'favorites'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query
                        ->where('name', 'like', "%{$filters['q']}%")
                        ->orWhere('slug', 'like', "%{$filters['q']}%");
                });
            })
            ->when($filters['status'] === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($filters['category'] !== '', fn (Builder $query) => $query->where('category_id', (int) $filters['category']))
            ->orderBy('category_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (TaskType $taskType): array => $this->taskTypeRow($taskType));

        return Inertia::render('Admin/TaskTypes/Index', [
            'taskTypes' => $taskTypes,
            'filters' => $filters,
            'categoryOptions' => $this->categoryOptions(includeInactive: true),
            'summary' => [
                'total' => TaskType::count(),
                'active' => TaskType::where('is_active', true)->count(),
                'inactive' => TaskType::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/TaskTypes/Create', [
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreTaskTypeRequest $request): RedirectResponse
    {
        $data = $request->taskTypeData();
        $data['slug'] = $this->uniqueSlug($request->filled('slug') ? $request->string('slug')->toString() : $data['name']);

        TaskType::create($data);

        return redirect()
            ->route('admin.task-types.index')
            ->with('success', 'Вид задания создан.');
    }

    public function edit(TaskType $taskType): Response
    {
        $taskType->load('category');

        return Inertia::render('Admin/TaskTypes/Edit', [
            'taskType' => $this->taskTypeFormPayload($taskType),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function update(UpdateTaskTypeRequest $request, TaskType $taskType): RedirectResponse
    {
        $data = $request->taskTypeData($taskType->is_active);
        $data['slug'] = $request->filled('slug')
            ? $this->uniqueSlug($request->string('slug')->toString(), $taskType)
            : $taskType->slug;

        $taskType->update($data);

        return redirect()
            ->route('admin.task-types.index')
            ->with('success', 'Вид задания обновлен.');
    }

    public function toggleActive(TaskType $taskType): RedirectResponse
    {
        $taskType->update(['is_active' => ! $taskType->is_active]);

        return back()->with('success', $taskType->is_active ? 'Вид задания включен.' : 'Вид задания скрыт.');
    }

    public function moveUp(TaskType $taskType): RedirectResponse
    {
        $this->move($taskType, -1);

        return back()->with('success', 'Порядок видов заданий обновлен.');
    }

    public function moveDown(TaskType $taskType): RedirectResponse
    {
        $this->move($taskType, 1);

        return back()->with('success', 'Порядок видов заданий обновлен.');
    }

    /**
     * @return array{q: string, status: string, category: string}
     */
    private function filters(Request $request): array
    {
        $status = $request->string('status')->toString();

        return [
            'q' => trim($request->string('q')->toString()),
            'status' => in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all',
            'category' => $request->filled('category') ? $request->string('category')->toString() : '',
        ];
    }

    private function taskTypeRow(TaskType $taskType): array
    {
        return [
            'id' => $taskType->id,
            'name' => $taskType->name,
            'slug' => $taskType->slug,
            'category' => $taskType->category?->name,
            'description' => $taskType->description,
            'sort_order' => $taskType->sort_order,
            'is_active' => $taskType->is_active,
            'status_label' => $taskType->is_active ? 'Активна' : 'Скрыта',
            'tasks_count' => $taskType->tasks_count,
            'favorites_count' => $taskType->favorites_count,
            'edit_url' => route('admin.task-types.edit', $taskType),
            'toggle_active_url' => route('admin.task-types.toggle-active', $taskType),
            'move_up_url' => route('admin.task-types.move-up', $taskType),
            'move_down_url' => route('admin.task-types.move-down', $taskType),
        ];
    }

    private function taskTypeFormPayload(TaskType $taskType): array
    {
        return [
            'id' => $taskType->id,
            'category_id' => $taskType->category_id,
            'name' => $taskType->name,
            'slug' => $taskType->slug,
            'description' => $taskType->description,
            'sort_order' => $taskType->sort_order,
            'is_active' => $taskType->is_active,
            'update_url' => route('admin.task-types.update', $taskType),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, is_active: bool}>
     */
    private function categoryOptions(bool $includeInactive = false): array
    {
        return Category::query()
            ->with('parent')
            ->when(! $includeInactive, fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->parent ? "{$category->parent->name} / {$category->name}" : $category->name,
                'is_active' => $category->is_active,
            ])
            ->values()
            ->all();
    }

    private function uniqueSlug(string $value, ?TaskType $except = null): string
    {
        $base = TaskoraSlug::make($value, 'task-type');
        $slug = $base;
        $counter = 2;

        while (TaskType::query()
            ->where('slug', $slug)
            ->when($except, fn (Builder $query) => $query->whereKeyNot($except->id))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function move(TaskType $taskType, int $offset): void
    {
        $siblings = TaskType::query()
            ->where('category_id', $taskType->category_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $index = $siblings->search(fn (TaskType $item): bool => $item->id === $taskType->id);

        if ($index === false) {
            return;
        }

        $swap = $siblings->get($index + $offset);

        if (! $swap) {
            return;
        }

        $currentOrder = $taskType->sort_order;
        $taskType->update(['sort_order' => $swap->sort_order]);
        $swap->update(['sort_order' => $currentOrder]);
    }
}
