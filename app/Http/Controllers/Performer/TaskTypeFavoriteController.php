<?php

namespace App\Http\Controllers\Performer;

use App\Http\Controllers\Controller;
use App\Models\PerformerFavoriteTaskType;
use App\Models\TaskType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskTypeFavoriteController extends Controller
{
    public function store(Request $request, TaskType $taskType): RedirectResponse
    {
        abort_unless($request->user()?->isPerformer(), 403);
        abort_unless($taskType->is_active, 404);

        PerformerFavoriteTaskType::firstOrCreate([
            'user_id' => $request->user()->id,
            'task_type_id' => $taskType->id,
        ]);

        return back()->with('success', 'Вид задания добавлен в избранное.');
    }

    public function destroy(Request $request, TaskType $taskType): RedirectResponse
    {
        abort_unless($request->user()?->isPerformer(), 403);

        PerformerFavoriteTaskType::query()
            ->where('user_id', $request->user()->id)
            ->where('task_type_id', $taskType->id)
            ->delete();

        return back()->with('success', 'Вид задания убран из избранного.');
    }
}
