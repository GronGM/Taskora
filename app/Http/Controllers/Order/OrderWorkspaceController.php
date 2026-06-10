<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrderWorkspaceController extends Controller
{
    public function __invoke(Request $request, Order $order): Response
    {
        Gate::authorize('viewWorkspace', $order);

        $order->load([
            'customer',
            'performer',
            'orderMessages.user',
            'orderFiles.user',
            'orderEvents.user',
            'activeDispute',
        ]);

        $role = $request->user()->isCustomer() ? 'customer' : 'performer';

        return Inertia::render('Orders/Workspace', [
            'role' => $role,
            'order' => $this->orderPayload($order, $role),
            'statusLabels' => $this->statusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
            'eventLabels' => $this->eventLabels(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order, string $role): array
    {
        return [
            'id' => $order->id,
            'title' => $order->title,
            'description' => $order->description,
            'source_type' => $order->source_type,
            'source_label' => $order->source_type === Order::SOURCE_SERVICE ? 'Услуга' : 'Задание',
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'review_hold_days' => $order->review_hold_days,
            'review_hold_started_at' => $order->review_hold_started_at?->format('d.m.Y H:i'),
            'review_hold_until' => $order->review_hold_until?->format('d.m.Y H:i'),
            'auto_release_at' => $order->auto_release_at?->format('d.m.Y H:i'),
            'released_at' => $order->released_at?->format('d.m.Y H:i'),
            'release_reason' => $order->release_reason,
            'release_reason_label' => $this->releaseReasonLabel($order->release_reason),
            'price' => $order->price,
            'performer_amount' => $order->performer_amount,
            'platform_fee_amount' => $order->platform_fee_amount,
            'platform_fee_percent' => $order->platform_fee_percent,
            'delivery_days' => $order->delivery_days,
            'customer' => $this->participantPayload($order->customer, 'Заказчик'),
            'performer' => $this->participantPayload($order->performer, 'Исполнитель'),
            'participant' => $role === 'customer' ? $order->performer?->name : $order->customer?->name,
            'back_url' => route("{$role}.orders.index"),
            'show_url' => route("{$role}.orders.show", $order),
            'workspace_url' => route("{$role}.orders.workspace", $order),
            'open_dispute_url' => route("{$role}.orders.disputes.create", $order),
            'active_dispute_url' => $order->activeDispute ? route("{$role}.disputes.show", $order->activeDispute) : null,
            'message_url' => route("{$role}.orders.messages.store", $order),
            'file_url' => route("{$role}.orders.files.store", $order),
            'mark_paid_url' => $role === 'customer' ? route('customer.orders.mark-paid', $order) : null,
            'request_revision_url' => $role === 'customer' ? route('customer.orders.request-revision', $order) : null,
            'complete_url' => $role === 'customer' ? route('customer.orders.complete', $order) : null,
            'cancel_url' => route("{$role}.orders.cancel", $order),
            'submit_work_url' => $role === 'performer' ? route('performer.orders.submit-work', $order) : null,
            'messages' => $order->orderMessages->map(fn (OrderMessage $message): array => $this->messagePayload($message, $order)),
            'files' => $order->orderFiles
                ->where('status', OrderFile::STATUS_AVAILABLE)
                ->values()
                ->map(fn (OrderFile $file): array => $this->filePayload($file, $order, $role)),
            'events' => $order->orderEvents->map(fn (OrderEvent $event): array => $this->eventPayload($event, $order)),
            'can' => [
                'mark_paid' => Gate::allows('markPaid', $order),
                'request_revision' => Gate::allows('requestRevision', $order),
                'complete' => Gate::allows('complete', $order),
                'cancel_as_customer' => Gate::allows('cancelAsCustomer', $order),
                'submit_work' => Gate::allows('submitWork', $order),
                'cancel_as_performer' => Gate::allows('cancelAsPerformer', $order),
                'open_dispute' => Gate::allows('create', [Dispute::class, $order]),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function participantPayload(?User $user, string $fallbackRole): array
    {
        return [
            'id' => $user?->id,
            'name' => $user?->name ?? 'Не указан',
            'role_label' => $user ? $this->userRoleLabel($user) : $fallbackRole,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function messagePayload(OrderMessage $message, Order $order): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'type' => $message->type,
            'author' => $message->user?->name ?? 'Система',
            'author_role' => $this->orderRoleLabel($message->user, $order),
            'created_at' => $message->created_at?->format('d.m.Y H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filePayload(OrderFile $file, Order $order, string $role): array
    {
        return [
            'id' => $file->id,
            'original_name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'status' => $file->status,
            'moderation_status' => $file->moderation_status,
            'author' => $file->user?->name ?? 'Участник заказа',
            'author_role' => $this->orderRoleLabel($file->user, $order),
            'created_at' => $file->created_at?->format('d.m.Y H:i'),
            'download_url' => route("{$role}.orders.files.download", [$order, $file]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(OrderEvent $event, Order $order): array
    {
        return [
            'id' => $event->id,
            'type' => $event->type,
            'label' => $this->eventLabels()[$event->type] ?? $event->type,
            'actor' => $event->user?->name ?? 'Система',
            'actor_role' => $this->orderRoleLabel($event->user, $order),
            'payload' => $event->payload,
            'summary' => $this->eventSummary($event),
            'created_at' => $event->created_at?->format('d.m.Y H:i'),
        ];
    }

    private function orderRoleLabel(?User $user, Order $order): string
    {
        if (! $user) {
            return 'Система';
        }

        if ($user->id === $order->customer_id) {
            return 'Заказчик';
        }

        if ($user->id === $order->performer_id) {
            return 'Исполнитель';
        }

        return $this->userRoleLabel($user);
    }

    private function userRoleLabel(User $user): string
    {
        return match ($user->role) {
            User::ROLE_PERFORMER => 'Исполнитель',
            User::ROLE_MODERATOR => 'Модератор',
            User::ROLE_ADMIN => 'Администратор',
            default => 'Заказчик',
        };
    }

    private function eventSummary(OrderEvent $event): ?string
    {
        $payload = $event->payload ?? [];

        return match ($event->type) {
            OrderEvent::TYPE_MESSAGE_SENT => 'Новое сообщение в чате заказа.',
            OrderEvent::TYPE_FILE_UPLOADED => isset($payload['file_name']) ? 'Загружен файл: '.$payload['file_name'] : 'Загружен файл.',
            OrderEvent::TYPE_CONTACT_BLOCKED => 'ContactGuard заблокировал контактные данные.',
            OrderEvent::TYPE_PAYMENT_STUB_PAID => 'Оплата отмечена локальной заглушкой.',
            OrderEvent::TYPE_WORK_SUBMITTED => 'Исполнитель отправил работу на проверку.',
            OrderEvent::TYPE_REVIEW_HOLD_STARTED => isset($payload['review_hold_until']) ? 'Срок проверки запущен до '.$payload['review_hold_until'].'.' : 'Срок проверки запущен.',
            OrderEvent::TYPE_REVISION_REQUESTED => 'Заказчик запросил доработку.',
            OrderEvent::TYPE_ORDER_COMPLETED => 'Заказ завершен.',
            OrderEvent::TYPE_FUNDS_RELEASED => 'Оплата разблокирована.',
            OrderEvent::TYPE_ORDER_CANCELED => 'Заказ отменен.',
            OrderEvent::TYPE_DISPUTE_OPENED => 'Открыт спор по заказу.',
            OrderEvent::TYPE_DISPUTE_MESSAGE_SENT => 'Новое сообщение в споре.',
            OrderEvent::TYPE_DISPUTE_UNDER_REVIEW => 'Спор взят в работу модератором.',
            OrderEvent::TYPE_DISPUTE_RESOLVED => 'Спор решен модератором.',
            OrderEvent::TYPE_FUNDS_REFUNDED => 'Средства возвращены заказчику.',
            OrderEvent::TYPE_REVISION_REQUESTED_BY_MODERATOR => 'Модератор вернул заказ на доработку.',
            OrderEvent::TYPE_ORDER_CREATED => 'Заказ создан.',
            default => null,
        };
    }

    private function releaseReasonLabel(?string $releaseReason): ?string
    {
        return match ($releaseReason) {
            Order::RELEASE_CUSTOMER_EARLY_ACCEPT => 'Досрочно принято заказчиком',
            Order::RELEASE_AUTO => 'Автоматически после срока проверки',
            Order::RELEASE_DISPUTE_TO_PERFORMER => 'Решение спора в пользу исполнителя',
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            Order::STATUS_AWAITING_PAYMENT => 'Ожидает оплаты',
            Order::STATUS_IN_PROGRESS => 'В работе',
            Order::STATUS_SUBMITTED_FOR_REVIEW => 'На проверке',
            Order::STATUS_REVISION_REQUESTED => 'Требуется доработка',
            Order::STATUS_COMPLETED => 'Завершен',
            Order::STATUS_DISPUTED => 'Спор',
            Order::STATUS_CANCELED => 'Отменен',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function paymentStatusLabels(): array
    {
        return [
            Order::PAYMENT_UNPAID => 'Не оплачен',
            Order::PAYMENT_HELD => 'Оплата удерживается',
            Order::PAYMENT_RELEASED => 'Оплата разблокирована',
            Order::PAYMENT_REFUNDED => 'Возврат',
            Order::PAYMENT_CANCELED => 'Отменена',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function eventLabels(): array
    {
        return [
            OrderEvent::TYPE_ORDER_CREATED => 'Заказ создан',
            OrderEvent::TYPE_PAYMENT_STUB_PAID => 'Оплата отмечена',
            OrderEvent::TYPE_WORK_SUBMITTED => 'Работа отправлена',
            OrderEvent::TYPE_REVIEW_HOLD_STARTED => 'Срок проверки запущен',
            OrderEvent::TYPE_REVISION_REQUESTED => 'Запрошена доработка',
            OrderEvent::TYPE_ORDER_COMPLETED => 'Заказ завершен',
            OrderEvent::TYPE_FUNDS_RELEASED => 'Оплата разблокирована',
            OrderEvent::TYPE_ORDER_CANCELED => 'Заказ отменен',
            OrderEvent::TYPE_DISPUTE_OPENED => 'Спор открыт',
            OrderEvent::TYPE_DISPUTE_MESSAGE_SENT => 'Сообщение в споре',
            OrderEvent::TYPE_DISPUTE_UNDER_REVIEW => 'Спор на рассмотрении',
            OrderEvent::TYPE_DISPUTE_RESOLVED => 'Спор решен',
            OrderEvent::TYPE_FUNDS_REFUNDED => 'Средства возвращены',
            OrderEvent::TYPE_REVISION_REQUESTED_BY_MODERATOR => 'Доработка по решению модератора',
            OrderEvent::TYPE_MESSAGE_SENT => 'Сообщение отправлено',
            OrderEvent::TYPE_FILE_UPLOADED => 'Файл загружен',
            OrderEvent::TYPE_CONTACT_BLOCKED => 'Контакт заблокирован',
        ];
    }
}
