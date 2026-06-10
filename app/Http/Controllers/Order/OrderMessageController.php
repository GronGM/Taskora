<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderMessageRequest;
use App\Models\ModerationFlag;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use App\Services\Orders\OrderEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class OrderMessageController extends Controller
{
    public function store(
        StoreOrderMessageRequest $request,
        Order $order,
        ContactGuard $contactGuard,
        OrderEventLogger $events,
    ): RedirectResponse {
        $body = $request->validated('body');
        $result = $contactGuard->check($body);

        if ($result->failedCheck()) {
            DB::transaction(function () use ($request, $order, $events, $result): void {
                $this->recordModerationFlag($request->user()->id, OrderMessage::class, null, 'contact_detected_in_order_message', $result);

                $events->contactBlocked($order, $request->user(), [
                    'context' => 'order_message',
                    'matched_type' => $result->matchedType,
                ]);
            });

            return back()
                ->withErrors(['body' => 'Сообщение не отправлено: в нем обнаружены контактные данные или предложение перейти вне Таскоры. Обсуждайте заказ и оплату внутри платформы.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $order, $events, $body): void {
            $message = $order->orderMessages()->create([
                'user_id' => $request->user()->id,
                'body' => $body,
                'type' => OrderMessage::TYPE_USER_MESSAGE,
            ]);

            $events->messageSent($order, $request->user(), [
                'message_id' => $message->id,
            ]);
        });

        return back()->with('success', 'Сообщение отправлено.');
    }

    private function recordModerationFlag(
        int $userId,
        string $entityType,
        ?int $entityId,
        string $reason,
        ContactGuardResult $result,
    ): void {
        ModerationFlag::create([
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'reason' => $reason,
            'matched_type' => $result->matchedType,
            'matched_value' => $result->matchedValue,
            'status' => ModerationFlag::STATUS_OPEN,
        ]);
    }
}
