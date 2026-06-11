<?php

namespace App\Http\Controllers\Performer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PerformerFavoriteCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryFavoriteController extends Controller
{
    public function store(Request $request, Category $category): RedirectResponse
    {
        abort_unless($request->user()?->isPerformer(), 403);
        abort_unless($category->is_active, 404);

        PerformerFavoriteCategory::firstOrCreate([
            'user_id' => $request->user()->id,
            'category_id' => $category->id,
        ]);

        return back()->with('success', 'Категория добавлена в избранное.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        abort_unless($request->user()?->isPerformer(), 403);

        PerformerFavoriteCategory::query()
            ->where('user_id', $request->user()->id)
            ->where('category_id', $category->id)
            ->delete();

        return back()->with('success', 'Категория убрана из избранного.');
    }
}
