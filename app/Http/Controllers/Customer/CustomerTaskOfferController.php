<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\TaskOffer;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CustomerTaskOfferController extends Controller
{
    public function reject(TaskOffer $offer, NotificationService $notifications): RedirectResponse
    {
        $offer->load(['performer', 'task']);

        Gate::authorize('reject', $offer);

        DB::transaction(function () use ($offer): void {
            $offer->update(['status' => TaskOffer::STATUS_REJECTED]);
            $offer->task()->where('offers_count', '>', 0)->decrement('offers_count');
        });

        $notifications->notifyUser(
            $offer->performer,
            'task_offer.rejected',
            'Отклик отклонен',
            "Заказчик отклонил ваш отклик на задание «{$offer->task->title}».",
            route('performer.offers.index'),
            [
                'actor_id' => request()->user()->id,
                'icon' => 'offer',
                'severity' => 'warning',
                'related_type' => TaskOffer::class,
                'related_id' => $offer->id,
                'task_id' => $offer->task_id,
            ],
        );

        return redirect()
            ->route('customer.tasks.show', $offer->task)
            ->with('success', 'Отклик отклонен.');
    }
}
