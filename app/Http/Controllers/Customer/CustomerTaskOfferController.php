<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\TaskOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CustomerTaskOfferController extends Controller
{
    public function reject(TaskOffer $offer): RedirectResponse
    {
        $offer->load('task');

        Gate::authorize('reject', $offer);

        DB::transaction(function () use ($offer): void {
            $offer->update(['status' => TaskOffer::STATUS_REJECTED]);
            $offer->task()->where('offers_count', '>', 0)->decrement('offers_count');
        });

        return redirect()
            ->route('customer.tasks.show', $offer->task)
            ->with('success', 'Отклик отклонен.');
    }
}
