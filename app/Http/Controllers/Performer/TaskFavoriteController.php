<?php

namespace App\Http\Controllers\Performer;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskFavorite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskFavoriteController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        abort_unless($request->user()?->isPerformer(), 403);
        abort_unless($task->status === Task::STATUS_PUBLISHED, 404);

        TaskFavorite::firstOrCreate([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Задание добавлено в избранное.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        abort_unless($request->user()?->isPerformer(), 403);

        TaskFavorite::query()
            ->where('task_id', $task->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return back()->with('success', 'Задание убрано из избранного.');
    }
}
