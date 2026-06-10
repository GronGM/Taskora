<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Review;
use App\Models\User;
use App\Services\Notifications\NotificationService;

class OrderEventLogger
{
    public function __construct(private readonly NotificationService $notifications) {}

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
        $event = $this->log($order, $user, OrderEvent::TYPE_ORDER_CREATED, $payload);

        $this->notifyOrderParticipant(
            $order,
            $order->performer,
            'order.created',
            $order->source_type === Order::SOURCE_SERVICE ? 'Новый заказ из услуги' : 'Новый заказ из отклика',
            "Создан заказ «{$order->title}».",
            fn (User $recipient): string => $this->orderUrlFor($recipient, $order),
            $this->orderMeta($order, $user, 'order', 'info'),
        );

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function paymentStubPaid(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        $event = $this->log($order, $user, OrderEvent::TYPE_PAYMENT_STUB_PAID, $payload);

        $this->notifyOrderParticipant(
            $order,
            $order->performer,
            'order.payment_held',
            'Заказ оплачен',
            "Заказ «{$order->title}» оплачен через локальную заглушку и перешел в работу.",
            fn (User $recipient): string => $this->orderUrlFor($recipient, $order),
            $this->orderMeta($order, $user, 'order', 'success'),
        );

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function workSubmitted(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        $event = $this->log($order, $user, OrderEvent::TYPE_WORK_SUBMITTED, $payload);

        $this->notifyOrderParticipant(
            $order,
            $order->customer,
            'order.work_submitted',
            'Работа отправлена на проверку',
            "Исполнитель отправил результат по заказу «{$order->title}».",
            fn (User $recipient): string => $this->orderUrlFor($recipient, $order),
            $this->orderMeta($order, $user, 'order', 'info'),
        );

        return $event;
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
        $event = $this->log($order, $user, OrderEvent::TYPE_REVISION_REQUESTED, $payload);

        $this->notifyOrderParticipant(
            $order,
            $order->performer,
            'order.revision_requested',
            'Запрошена доработка',
            "Заказчик запросил доработку по заказу «{$order->title}».",
            fn (User $recipient): string => $this->orderUrlFor($recipient, $order),
            $this->orderMeta($order, $user, 'order', 'warning'),
        );

        return $event;
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
        $event = $this->log($order, $user, OrderEvent::TYPE_FUNDS_RELEASED, $payload);
        $releaseReason = $payload['release_reason'] ?? $order->release_reason;

        if (in_array($releaseReason, [Order::RELEASE_CUSTOMER_EARLY_ACCEPT, Order::RELEASE_AUTO], true)) {
            $type = $releaseReason === Order::RELEASE_AUTO ? 'order.auto_released' : 'order.completed';
            $title = $releaseReason === Order::RELEASE_AUTO
                ? 'Оплата разблокирована автоматически'
                : 'Заказ завершен';
            $body = $releaseReason === Order::RELEASE_AUTO
                ? "Срок проверки заказа «{$order->title}» истек, оплата разблокирована."
                : "Заказ «{$order->title}» завершен, оплата разблокирована.";

            $this->notifyOrderParticipants(
                $order,
                $type,
                $title,
                $body,
                fn (User $recipient): string => $this->orderUrlFor($recipient, $order),
                $this->orderMeta($order, null, 'order', 'success'),
            );
        }

        $this->notifyCustomerReviewRequested($order);

        return $event;
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
    public function disputeOpened(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        $event = $this->log($order, $user, OrderEvent::TYPE_DISPUTE_OPENED, $payload);
        $disputeId = isset($payload['dispute_id']) ? (int) $payload['dispute_id'] : null;

        if ($disputeId !== null) {
            $this->notifyOrderParticipants(
                $order,
                'dispute.opened',
                'Открыт спор по заказу',
                "По заказу «{$order->title}» открыт спор. Авторазблокировка оплаты остановлена.",
                fn (User $recipient): string => $this->disputeUrlFor($recipient, $disputeId),
                $this->orderMeta($order, $user, 'dispute', 'warning', $disputeId),
            );

            $this->notifications->notifyModeratorsAndAdmins(
                'dispute.opened.moderation',
                'Новый спор требует проверки',
                "Открыт спор по заказу «{$order->title}».",
                route('moderator.disputes.show', ['dispute' => $disputeId]),
                $this->orderMeta($order, $user, 'dispute', 'warning', $disputeId),
            );
        }

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function disputeMessageSent(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        $event = $this->log($order, $user, OrderEvent::TYPE_DISPUTE_MESSAGE_SENT, $payload);
        $disputeId = isset($payload['dispute_id']) ? (int) $payload['dispute_id'] : null;

        if ($disputeId !== null) {
            $meta = $this->orderMeta($order, $user, 'dispute', 'info', $disputeId);
            $this->notifications->notifyUsers(
                User::query()->whereIn('role', [User::ROLE_MODERATOR, User::ROLE_ADMIN])->get(),
                'dispute.message_sent',
                'Новое сообщение в споре',
                "В споре по заказу «{$order->title}» появилось новое сообщение.",
                route('moderator.disputes.show', ['dispute' => $disputeId]),
                $meta,
            );

            foreach ($this->orderParticipants($order) as $participant) {
                $this->notifications->notifyUser(
                    $participant,
                    'dispute.message_sent',
                    'Новое сообщение в споре',
                    "В споре по заказу «{$order->title}» появилось новое сообщение.",
                    $this->disputeUrlFor($participant, $disputeId),
                    $meta,
                );
            }
        }

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function disputeUnderReview(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        $event = $this->log($order, $user, OrderEvent::TYPE_DISPUTE_UNDER_REVIEW, $payload);
        $disputeId = isset($payload['dispute_id']) ? (int) $payload['dispute_id'] : null;

        if ($disputeId !== null) {
            $this->notifyOrderParticipants(
                $order,
                'dispute.under_review',
                'Спор взят в работу',
                "Модератор начал рассматривать спор по заказу «{$order->title}».",
                fn (User $recipient): string => $this->disputeUrlFor($recipient, $disputeId),
                $this->orderMeta($order, $user, 'dispute', 'info', $disputeId),
            );
        }

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function disputeResolved(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        $event = $this->log($order, $user, OrderEvent::TYPE_DISPUTE_RESOLVED, $payload);
        $disputeId = isset($payload['dispute_id']) ? (int) $payload['dispute_id'] : null;

        if ($disputeId !== null) {
            $this->notifyOrderParticipants(
                $order,
                'dispute.resolved',
                'Спор решен',
                "Модератор принял решение по спору в заказе «{$order->title}».",
                fn (User $recipient): string => $this->disputeUrlFor($recipient, $disputeId),
                $this->orderMeta($order, $user, 'dispute', 'success', $disputeId),
            );
        }

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fundsRefunded(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_FUNDS_REFUNDED, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function revisionRequestedByModerator(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_REVISION_REQUESTED_BY_MODERATOR, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function messageSent(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        $event = $this->log($order, $user, OrderEvent::TYPE_MESSAGE_SENT, $payload);

        $this->notifyOrderParticipants(
            $order,
            'order.message_sent',
            'Новое сообщение в заказе',
            "В заказе «{$order->title}» появилось новое сообщение.",
            fn (User $recipient): string => $this->workspaceUrlFor($recipient, $order),
            $this->orderMeta($order, $user, 'message', 'info'),
        );

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function fileUploaded(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        $event = $this->log($order, $user, OrderEvent::TYPE_FILE_UPLOADED, $payload);

        $this->notifyOrderParticipants(
            $order,
            'order.file_uploaded',
            'Новый файл в заказе',
            "В заказе «{$order->title}» загружен новый файл.",
            fn (User $recipient): string => $this->workspaceUrlFor($recipient, $order),
            $this->orderMeta($order, $user, 'file', 'info'),
        );

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function contactBlocked(Order $order, ?User $user, array $payload = []): OrderEvent
    {
        return $this->log($order, $user, OrderEvent::TYPE_CONTACT_BLOCKED, $payload);
    }

    /**
     * @return array<int, User>
     */
    private function orderParticipants(Order $order): array
    {
        $order->loadMissing(['customer', 'performer']);

        return array_values(array_filter([$order->customer, $order->performer]));
    }

    /**
     * @param  callable(User): string  $url
     * @param  array<string, mixed>  $meta
     */
    private function notifyOrderParticipants(
        Order $order,
        string $type,
        string $title,
        string $body,
        callable $url,
        array $meta,
    ): void {
        foreach ($this->orderParticipants($order) as $recipient) {
            $this->notifyOrderParticipant($order, $recipient, $type, $title, $body, $url, $meta);
        }
    }

    /**
     * @param  callable(User): string  $url
     * @param  array<string, mixed>  $meta
     */
    private function notifyOrderParticipant(
        Order $order,
        ?User $recipient,
        string $type,
        string $title,
        string $body,
        callable $url,
        array $meta,
    ): void {
        if (! $recipient instanceof User) {
            return;
        }

        $this->notifications->notifyUser($recipient, $type, $title, $body, $url($recipient), $meta);
    }

    private function orderUrlFor(User $user, Order $order): string
    {
        return $user->isPerformer()
            ? route('performer.orders.show', $order)
            : route('customer.orders.show', $order);
    }

    private function workspaceUrlFor(User $user, Order $order): string
    {
        return $user->isPerformer()
            ? route('performer.orders.workspace', $order)
            : route('customer.orders.workspace', $order);
    }

    private function disputeUrlFor(User $user, int $disputeId): string
    {
        if ($user->isModerator() || $user->isAdmin()) {
            return route('moderator.disputes.show', ['dispute' => $disputeId]);
        }

        return $user->isPerformer()
            ? route('performer.disputes.show', ['dispute' => $disputeId])
            : route('customer.disputes.show', ['dispute' => $disputeId]);
    }

    private function notifyCustomerReviewRequested(Order $order): void
    {
        $order->loadMissing(['customer', 'review']);

        if (
            ! $order->customer instanceof User
            || $order->review !== null
            || $order->status !== Order::STATUS_COMPLETED
            || $order->payment_status !== Order::PAYMENT_RELEASED
        ) {
            return;
        }

        $this->notifications->notifyUser(
            $order->customer,
            'order.review_requested',
            'Заказ завершен — оставьте отзыв исполнителю',
            "Расскажите о результате по заказу «{$order->title}». Отзыв помогает заказчикам выбирать исполнителей в Таскоре.",
            route('customer.orders.review.create', $order),
            [
                'icon' => 'review',
                'severity' => 'success',
                'related_type' => Review::class,
                'related_id' => $order->id,
                'order_id' => $order->id,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function orderMeta(Order $order, ?User $actor, string $icon, string $severity, ?int $relatedId = null): array
    {
        return [
            'actor_id' => $actor?->id,
            'icon' => $icon,
            'severity' => $severity,
            'related_type' => $relatedId ? 'dispute' : Order::class,
            'related_id' => $relatedId ?? $order->id,
            'order_id' => $order->id,
        ];
    }
}
