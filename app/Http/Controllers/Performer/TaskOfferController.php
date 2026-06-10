<?php

namespace App\Http\Controllers\Performer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Performer\StoreTaskOfferRequest;
use App\Models\Task;
use App\Models\TaskOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskOfferController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', TaskOffer::class);

        $offers = request()->user()
            ->taskOffers()
            ->with(['task.category', 'task.customer'])
            ->latest()
            ->get()
            ->map(fn (TaskOffer $offer): array => [
                'id' => $offer->id,
                'message' => $offer->message,
                'price' => $offer->price,
                'delivery_days' => $offer->delivery_days,
                'status' => $offer->status,
                'created_at' => $offer->created_at?->format('d.m.Y H:i'),
                'withdraw_url' => route('performer.task-offers.withdraw', $offer),
                'task' => [
                    'id' => $offer->task?->id,
                    'title' => $offer->task?->title,
                    'url' => $offer->task?->status === Task::STATUS_PUBLISHED ? $offer->task->url : null,
                    'deadline_at' => $offer->task?->deadline_at?->format('d.m.Y'),
                    'category' => $offer->task?->category?->name,
                    'customer' => $offer->task?->customer?->name,
                ],
            ]);

        return Inertia::render('Performer/Offers/Index', [
            'offers' => $offers,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function store(StoreTaskOfferRequest $request, Task $task): RedirectResponse
    {
        DB::transaction(function () use ($request, $task): void {
            TaskOffer::create([
                ...$request->offerData(),
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'status' => TaskOffer::STATUS_SUBMITTED,
            ]);

            $task->increment('offers_count');
        });

        return redirect()
            ->route('performer.offers.index')
            ->with('success', 'Отклик отправлен заказчику.');
    }

    public function withdraw(TaskOffer $offer): RedirectResponse
    {
        $offer->load('task');

        Gate::authorize('withdraw', $offer);

        DB::transaction(function () use ($offer): void {
            $offer->update(['status' => TaskOffer::STATUS_WITHDRAWN]);
            $offer->task()->where('offers_count', '>', 0)->decrement('offers_count');
        });

        return redirect()
            ->route('performer.offers.index')
            ->with('success', 'Отклик отозван.');
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            TaskOffer::STATUS_SUBMITTED => 'Отправлен',
            TaskOffer::STATUS_WITHDRAWN => 'Отозван',
            TaskOffer::STATUS_REJECTED => 'Отклонен',
            TaskOffer::STATUS_ACCEPTED => 'Принят',
        ];
    }
}
