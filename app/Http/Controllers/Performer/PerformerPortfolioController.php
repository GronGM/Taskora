<?php

namespace App\Http\Controllers\Performer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Performer\StorePortfolioItemRequest;
use App\Http\Requests\Performer\UpdatePortfolioItemRequest;
use App\Models\Category;
use App\Models\PerformerPortfolioItem;
use App\Models\PerformerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PerformerPortfolioController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', PerformerPortfolioItem::class);

        $profile = $this->profileFor(request()->user());

        $items = $profile->portfolioItems()
            ->with('category')
            ->get()
            ->map(fn (PerformerPortfolioItem $item): array => $this->itemListPayload($item));

        return Inertia::render('Performer/Portfolio/Index', [
            'items' => $items,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', PerformerPortfolioItem::class);

        return Inertia::render('Performer/Portfolio/Create', [
            'categories' => $this->categoryOptions(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function store(StorePortfolioItemRequest $request): RedirectResponse
    {
        $profile = $this->profileFor($request->user());

        $item = $profile->portfolioItems()->create([
            ...$request->itemData(),
            'image_path' => $request->file('image')?->store("performer-portfolio/{$profile->id}", 'public'),
            'file_path' => $request->file('file')?->store("performer-portfolio/{$profile->id}", 'public'),
        ]);

        return redirect()
            ->route('performer.portfolio.edit', $item)
            ->with('success', 'Работа портфолио создана.');
    }

    public function edit(PerformerPortfolioItem $item): Response
    {
        Gate::authorize('view', $item);

        $item->load('category');

        return Inertia::render('Performer/Portfolio/Edit', [
            'item' => $this->itemFormPayload($item),
            'categories' => $this->categoryOptions(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function update(UpdatePortfolioItemRequest $request, PerformerPortfolioItem $item): RedirectResponse
    {
        $data = $request->itemData();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store("performer-portfolio/{$item->performer_profile_id}", 'public');
        }

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store("performer-portfolio/{$item->performer_profile_id}", 'public');
        }

        $oldImage = $item->image_path;
        $oldFile = $item->file_path;

        $item->update($data);

        if (($data['image_path'] ?? null) && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        if (($data['file_path'] ?? null) && $oldFile) {
            Storage::disk('public')->delete($oldFile);
        }

        return redirect()
            ->route('performer.portfolio.edit', $item)
            ->with('success', 'Работа портфолио обновлена.');
    }

    public function hide(PerformerPortfolioItem $item): RedirectResponse
    {
        Gate::authorize('hide', $item);

        $item->update([
            'status' => PerformerPortfolioItem::STATUS_HIDDEN,
            'is_public' => false,
        ]);

        return redirect()
            ->route('performer.portfolio.index')
            ->with('success', 'Работа скрыта из публичного профиля.');
    }

    public function publish(PerformerPortfolioItem $item): RedirectResponse
    {
        Gate::authorize('publish', $item);

        $item->update([
            'status' => PerformerPortfolioItem::STATUS_PUBLISHED,
            'is_public' => true,
        ]);

        return redirect()
            ->route('performer.portfolio.index')
            ->with('success', 'Работа опубликована в профиле.');
    }

    public function destroy(PerformerPortfolioItem $item): RedirectResponse
    {
        Gate::authorize('delete', $item);

        $imagePath = $item->image_path;
        $filePath = $item->file_path;

        $item->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        if ($filePath) {
            Storage::disk('public')->delete($filePath);
        }

        return redirect()
            ->route('performer.portfolio.index')
            ->with('success', 'Работа портфолио удалена.');
    }

    private function profileFor(User $user): PerformerProfile
    {
        abort_unless($user->isPerformer(), 403);

        return PerformerProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'verification_status' => PerformerProfile::STATUS_NOT_SUBMITTED,
                'is_public' => true,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function itemListPayload(PerformerPortfolioItem $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'category' => $item->category?->name,
            'status' => $item->status,
            'is_public' => $item->is_public,
            'image_url' => $item->image_url,
            'file_url' => $item->file_url,
            'external_url' => $item->external_url,
            'edit_url' => route('performer.portfolio.edit', $item),
            'hide_url' => route('performer.portfolio.hide', $item),
            'publish_url' => route('performer.portfolio.publish', $item),
            'delete_url' => route('performer.portfolio.destroy', $item),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemFormPayload(PerformerPortfolioItem $item): array
    {
        return [
            ...$this->itemListPayload($item),
            'category_id' => $item->category_id,
            'sort_order' => $item->sort_order,
            'update_url' => route('performer.portfolio.update', $item),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categoryOptions(): array
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
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            PerformerPortfolioItem::STATUS_DRAFT => 'Черновик',
            PerformerPortfolioItem::STATUS_PUBLISHED => 'Опубликована',
            PerformerPortfolioItem::STATUS_HIDDEN => 'Скрыта',
        ];
    }
}
