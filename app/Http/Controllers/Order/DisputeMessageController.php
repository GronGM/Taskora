<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreDisputeMessageRequest;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\ModerationFlag;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use App\Services\Orders\OrderEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class DisputeMessageController extends Controller
{
    public function store(
        StoreDisputeMessageRequest $request,
        Dispute $dispute,
        ContactGuard $contactGuard,
        OrderEventLogger $events,
    ): RedirectResponse {
        $body = $request->validated('body');
        $result = $contactGuard->check($body);
        $dispute->load('order');

        if ($result->failedCheck()) {
            DB::transaction(function () use ($request, $dispute, $events, $result): void {
                $this->recordModerationFlag($request->user()->id, DisputeMessage::class, null, 'contact_detected_in_dispute_message', $result);

                $events->contactBlocked($dispute->order, $request->user(), [
                    'context' => 'dispute_message',
                    'dispute_id' => $dispute->id,
                    'matched_type' => $result->matchedType,
                ]);
            });

            return back()
                ->withErrors(['body' => 'Сообщение не отправлено: в нем обнаружены контактные данные или предложение перейти вне Таскоры. Обсуждайте заказ и оплату внутри платформы.'])
                ->withInput();
        }

        DB::transaction(function () use ($request, $dispute, $events, $body): void {
            $message = $dispute->messages()->create([
                'user_id' => $request->user()->id,
                'body' => $body,
                'is_system' => false,
            ]);

            $events->disputeMessageSent($dispute->order, $request->user(), [
                'dispute_id' => $dispute->id,
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
