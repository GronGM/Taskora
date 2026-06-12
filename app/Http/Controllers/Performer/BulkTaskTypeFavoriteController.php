<?php

namespace App\Http\Controllers\Performer;

use App\Http\Controllers\Controller;
use App\Models\PerformerFavoriteTaskType;
use App\Models\TaskType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BulkTaskTypeFavoriteController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isPerformer(), 403);

        $validated = $request->validate([
            'task_type_slugs' => ['nullable', 'array'],
            'task_type_slugs.*' => ['string', 'max:120'],
        ]);

        $slugs = collect($validated['task_type_slugs'] ?? [])
            ->map(fn ($slug): string => trim((string) $slug))
            ->filter()
            ->unique()
            ->values();

        if ($slugs->isEmpty()) {
            return back()->with('success', 'Выберите виды заданий для добавления.');
        }

        $taskTypes = TaskType::query()
            ->active()
            ->whereHas('category', fn (Builder $query) => $query->where('is_active', true))
            ->whereIn('slug', $slugs)
            ->get();

        foreach ($taskTypes as $taskType) {
            PerformerFavoriteTaskType::firstOrCreate([
                'user_id' => $request->user()->id,
                'task_type_id' => $taskType->id,
            ]);
        }

        return back()->with('success', 'Виды заданий добавлены в избранное.');
    }
}
