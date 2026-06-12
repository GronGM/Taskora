<?php

namespace App\Services\Messages;

use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\ModerationFlag;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use App\Services\Moderation\ContactGuard;
use App\Services\Moderation\ContactGuardResult;
use App\Services\Orders\OrderEventLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessageDeliveryService
{
    public function __construct(
        private readonly ContactGuard $contactGuard,
        private readonly OrderEventLogger $events,
    ) {}

    public function sendOrderMessage(User $user, Order $order, string $body): OrderMessage
    {
        $result = $this->contactGuard->check($body);

        if ($result->failedCheck()) {
            DB::transaction(function () use ($user, $order, $result): void {
                $this->recordModerationFlag($user->id, OrderMessage::class, null, 'contact_detected_in_order_message', $result);

                $this->events->contactBlocked($order, $user, [
                    'context' => 'order_message',
                    'matched_type' => $result->matchedType,
                ]);
            });

            throw ValidationException::withMessages([
                'body' => 'Сообщение не отправлено: в нем обнаружены контактные данные или предложение перейти вне Таскоры. Обсуждайте заказ и оплату внутри платформы.',
            ]);
        }

        return DB::transaction(function () use ($user, $order, $body): OrderMessage {
            $message = $order->orderMessages()->create([
                'user_id' => $user->id,
                'body' => $body,
                'type' => OrderMessage::TYPE_USER_MESSAGE,
            ]);

            $this->events->messageSent($order, $user, [
                'message_id' => $message->id,
            ]);

            return $message;
        });
    }

    public function sendDisputeMessage(User $user, Dispute $dispute, string $body): DisputeMessage
    {
        $result = $this->contactGuard->check($body);
        $dispute->loadMissing('order');

        if ($result->failedCheck()) {
            DB::transaction(function () use ($user, $dispute, $result): void {
                $this->recordModerationFlag($user->id, DisputeMessage::class, null, 'contact_detected_in_dispute_message', $result);

                $this->events->contactBlocked($dispute->order, $user, [
                    'context' => 'dispute_message',
                    'dispute_id' => $dispute->id,
                    'matched_type' => $result->matchedType,
                ]);
            });

            throw ValidationException::withMessages([
                'body' => 'Сообщение не отправлено: в нем обнаружены контактные данные или предложение перейти вне Таскоры. Обсуждайте заказ и оплату внутри платформы.',
            ]);
        }

        return DB::transaction(function () use ($user, $dispute, $body): DisputeMessage {
            $message = $dispute->messages()->create([
                'user_id' => $user->id,
                'body' => $body,
                'is_system' => false,
            ]);

            $this->events->disputeMessageSent($dispute->order, $user, [
                'dispute_id' => $dispute->id,
                'message_id' => $message->id,
            ]);

            return $message;
        });
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
