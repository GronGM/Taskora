<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreDisputeMessageRequest;
use App\Models\Dispute;
use App\Services\Messages\MessageDeliveryService;
use Illuminate\Http\RedirectResponse;

class DisputeMessageController extends Controller
{
    public function store(
        StoreDisputeMessageRequest $request,
        Dispute $dispute,
        MessageDeliveryService $messages,
    ): RedirectResponse {
        $messages->sendDisputeMessage($request->user(), $dispute, $request->validated('body'));

        return back()->with('success', 'Сообщение отправлено.');
    }
}
