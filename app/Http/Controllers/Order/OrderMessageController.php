<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderMessageRequest;
use App\Models\Order;
use App\Services\Messages\MessageDeliveryService;
use Illuminate\Http\RedirectResponse;

class OrderMessageController extends Controller
{
    public function store(
        StoreOrderMessageRequest $request,
        Order $order,
        MessageDeliveryService $messages,
    ): RedirectResponse {
        $messages->sendOrderMessage($request->user(), $order, $request->validated('body'));

        return back()->with('success', 'Сообщение отправлено.');
    }
}
