<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Support\TaskoraSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $categories = Category::query()
            ->with('parent')
            ->withCount(['services', 'tasks', 'taskTypes'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query
                        ->where('name', 'like', "%{$filters['q']}%")
                        ->orWhere('slug', 'like', "%{$filters['q']}%");
                });
            })
            ->when($filters['status'] === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category): array => $this->categoryRow($category));

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'filters' => $filters,
            'summary' => [
                'total' => Category::count(),
                'active' => Category::where('is_active', true)->count(),
                'inactive' => Category::where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Create', [
            'parentOptions' => $this->parentOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->categoryData();
        $data['slug'] = $this->uniqueSlug($request->filled('slug') ? $request->string('slug')->toString() : $data['name']);

        Category::create($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория создана.');
    }

    public function edit(Category $category): Response
    {
        $category->load('parent');

        return Inertia::render('Admin/Categories/Edit', [
            'category' => $this->categoryFormPayload($category),
            'parentOptions' => $this->parentOptions($category),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->categoryData($category->is_active);
        $data['slug'] = $request->filled('slug')
            ? $this->uniqueSlug($request->string('slug')->toString(), $category)
            : $category->slug;

        $category->update($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория обновлена.');
    }

    public function toggleActive(Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('success', $category->is_active ? 'Категория включена.' : 'Категория скрыта.');
    }

    public function moveUp(Category $category): RedirectResponse
    {
        $this->move($category, -1);

        return back()->with('success', 'Порядок категорий обновлен.');
    }

    public function moveDown(Category $category): RedirectResponse
    {
        $this->move($category, 1);

        return back()->with('success', 'Порядок категорий обновлен.');
    }

    /**
     * @return array{q: string, status: string}
     */
    private function filters(Request $request): array
    {
        $status = $request->string('status')->toString();

        return [
            'q' => trim($request->string('q')->toString()),
            'status' => in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all',
        ];
    }

    private function categoryRow(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent' => $category->parent?->name,
            'description' => $category->description,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'status_label' => $category->is_active ? 'Активна' : 'Скрыта',
            'services_count' => $category->services_count,
            'tasks_count' => $category->tasks_count,
            'task_types_count' => $category->task_types_count,
            'edit_url' => route('admin.categories.edit', $category),
            'toggle_active_url' => route('admin.categories.toggle-active', $category),
            'move_up_url' => route('admin.categories.move-up', $category),
            'move_down_url' => route('admin.categories.move-down', $category),
        ];
    }

    private function categoryFormPayload(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'description' => $category->description,
            'icon' => $category->icon,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
            'update_url' => route('admin.categories.update', $category),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function parentOptions(?Category $category = null): array
    {
        $excludedIds = $category ? [$category->id, ...$this->descendantIds($category)] : [];

        return Category::query()
            ->with('parent')
            ->when($excludedIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludedIds))
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $option): array => [
                'id' => $option->id,
                'name' => $option->parent ? "{$option->parent->name} / {$option->name}" : $option->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function descendantIds(Category $category): array
    {
        $ids = [];
        $frontier = [$category->id];

        while ($frontier !== []) {
            $children = Category::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $frontier = array_values(array_diff($children, $ids));
            $ids = [...$ids, ...$frontier];
        }

        return $ids;
    }

    private function uniqueSlug(string $value, ?Category $except = null): string
    {
        $base = TaskoraSlug::make($value, 'category');
        $slug = $base;
        $counter = 2;

        while (Category::query()
            ->where('slug', $slug)
            ->when($except, fn (Builder $query) => $query->whereKeyNot($except->id))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function move(Category $category, int $offset): void
    {
        $siblings = Category::query()
            ->when(
                $category->parent_id === null,
                fn (Builder $query) => $query->whereNull('parent_id'),
                fn (Builder $query) => $query->where('parent_id', $category->parent_id),
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $index = $siblings->search(fn (Category $item): bool => $item->id === $category->id);

        if ($index === false) {
            return;
        }

        $swap = $siblings->get($index + $offset);

        if (! $swap) {
            return;
        }

        $currentOrder = $category->sort_order;
        $category->update(['sort_order' => $swap->sort_order]);
        $swap->update(['sort_order' => $currentOrder]);
    }
}
