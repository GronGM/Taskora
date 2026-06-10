<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;

class OrderEventLogger
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function log(Order $order, ?User $user, string $type, array $payload = []): OrderEvent
    {
        return $order->orderEvents()->create([
            'user_id' => $user?->id,
            'type' => $type,
            'payload' => $payload === [] ? null : $payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function orderCreated(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_ORDER_CREATED, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function paymentStubPaid(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_PAYMENT_STUB_PAID, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function workSubmitted(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_WORK_SUBMITTED, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function reviewHoldStarted(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_REVIEW_HOLD_STARTED, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function revisionRequested(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_REVISION_REQUESTED, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function orderCompleted(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_ORDER_COMPLETED, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fundsReleased(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_FUNDS_RELEASED, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function orderCanceled(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_ORDER_CANCELED, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function messageSent(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_MESSAGE_SENT, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fileUploaded(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_FILE_UPLOADED, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function contactBlocked(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_CONTACT_BLOCKED, $payload);
    }
}
