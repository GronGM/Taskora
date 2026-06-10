<?php

namespace App\Http\Controllers\Moderator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Moderator\ResolveDisputeRequest;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\OrderSubmission;
use App\Models\User;
use App\Services\Orders\OrderEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ModeratorDisputeController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewModeratorAny', Dispute::class);

        $status = $request->string('status')->toString() ?: Dispute::STATUS_OPEN;
        $allowedStatuses = [Dispute::STATUS_OPEN, Dispute::STATUS_UNDER_REVIEW, Dispute::STATUS_RESOLVED];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = Dispute::STATUS_OPEN;
        }

        $disputes = Dispute::query()
            ->with(['order.customer', 'order.performer', 'openedBy'])
            ->where('status', $status)
            ->latest()
            ->get()
            ->map(fn (Dispute $dispute): array => [
                'id' => $dispute->id,
                'status' => $dispute->status,
                'status_label' => Dispute::statusLabels()[$dispute->status] ?? $dispute->status,
                'reason' => $dispute->reason,
                'reason_label' => Dispute::reasonLabels()[$dispute->reason] ?? $dispute->reason,
                'opened_by' => $dispute->openedBy?->name,
                'order_id' => $dispute->order_id,
                'order_title' => $dispute->order?->title,
                'customer' => $dispute->order?->customer?->name,
                'performer' => $dispute->order?->performer?->name,
                'price' => $dispute->order?->price,
                'created_at' => $dispute->created_at?->format('d.m.Y H:i'),
                'show_url' => route('moderator.disputes.show', $dispute),
            ]);

        return Inertia::render('Moderator/Disputes/Index', [
            'disputes' => $disputes,
            'currentStatus' => $status,
            'statusTabs' => $this->optionPayload(collect(Dispute::statusLabels())->only($allowedStatuses)->all()),
        ]);
    }

    public function show(Dispute $dispute): Response
    {
        Gate::authorize('view', $dispute);

        $dispute->load([
            'openedBy',
            'resolvedBy',
            'messages.user',
            'order.customer',
            'order.performer',
            'order.service',
            'order.task',
            'order.taskOffer',
            'order.orderMessages.user',
            'order.orderFiles.user',
            'order.orderEvents.user',
            'order.submissions.user',
        ]);

        return Inertia::render('Moderator/Disputes/Show', [
            'dispute' => $this->disputePayload($dispute),
            'statusLabels' => Dispute::statusLabels(),
            'reasonLabels' => Dispute::reasonLabels(),
            'resolutionLabels' => Dispute::resolutionLabels(),
            'resolutionOptions' => $this->optionPayload(Dispute::resolutionLabels()),
        ]);
    }

    public function take(Dispute $dispute, OrderEventLogger $events): RedirectResponse
    {
        Gate::authorize('take', $dispute);
        $user = request()->user();

        DB::transaction(function () use ($dispute, $user, $events): void {
            $dispute->load('order');
            $dispute->update(['status' => Dispute::STATUS_UNDER_REVIEW]);
            $dispute->messages()->create([
                'user_id' => $user->id,
                'body' => 'Модератор взял спор в работу.',
                'is_system' => true,
            ]);

            $events->disputeUnderReview($dispute->order, $user, [
                'dispute_id' => $dispute->id,
            ]);
        });

        return redirect()
            ->route('moderator.disputes.show', $dispute)
            ->with('success', 'Спор взят в работу.');
    }

    public function resolve(ResolveDisputeRequest $request, Dispute $dispute, OrderEventLogger $events): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($dispute, $validated, $user, $events): void {
            $dispute->load(['order.submissions']);
            $order = $dispute->order;
            $resolvedAt = now();

            $dispute->update([
                'status' => Dispute::STATUS_RESOLVED,
                'resolution' => $validated['resolution'],
                'moderator_comment' => $validated['moderator_comment'],
                'resolved_by' => $user->id,
                'resolved_at' => $resolvedAt,
            ]);

            match ($validated['resolution']) {
                Dispute::RESOLUTION_RELEASE_TO_PERFORMER => $this->releaseToPerformer($order, $dispute, $user, $events, $resolvedAt),
                Dispute::RESOLUTION_REFUND_TO_CUSTOMER => $this->refundToCustomer($order, $dispute, $user, $events, $resolvedAt),
                Dispute::RESOLUTION_RETURN_TO_REVISION => $this->returnToRevision($order, $dispute, $user, $events, $resolvedAt),
            };

            $dispute->messages()->create([
                'user_id' => $user->id,
                'body' => 'Спор решен: '.(Dispute::resolutionLabels()[$validated['resolution']] ?? $validated['resolution']).'. '.$validated['moderator_comment'],
                'is_system' => true,
            ]);
        });

        return redirect()
            ->route('moderator.disputes.show', $dispute)
            ->with('success', 'Решение по спору сохранено.');
    }

    private function releaseToPerformer(Order $order, Dispute $dispute, User $user, OrderEventLogger $events, mixed $resolvedAt): void
    {
        $order->update([
            'status' => Order::STATUS_COMPLETED,
            'payment_status' => Order::PAYMENT_RELEASED,
            'completed_at' => $resolvedAt,
            'released_at' => $resolvedAt,
            'release_reason' => Order::RELEASE_DISPUTE_TO_PERFORMER,
            'review_hold_until' => null,
            'auto_release_at' => null,
        ]);

        $events->disputeResolved($order, $user, [
            'dispute_id' => $dispute->id,
            'resolution' => Dispute::RESOLUTION_RELEASE_TO_PERFORMER,
        ]);

        $events->fundsReleased($order, $user, [
            'dispute_id' => $dispute->id,
            'release_reason' => Order::RELEASE_DISPUTE_TO_PERFORMER,
            'released_at' => $resolvedAt->toISOString(),
        ]);
    }

    private function refundToCustomer(Order $order, Dispute $dispute, User $user, OrderEventLogger $events, mixed $resolvedAt): void
    {
        $order->update([
            'status' => Order::STATUS_CANCELED,
            'payment_status' => Order::PAYMENT_REFUNDED,
            'canceled_at' => $resolvedAt,
            'review_hold_until' => null,
            'auto_release_at' => null,
        ]);

        $events->disputeResolved($order, $user, [
            'dispute_id' => $dispute->id,
            'resolution' => Dispute::RESOLUTION_REFUND_TO_CUSTOMER,
        ]);

        $events->fundsRefunded($order, $user, [
            'dispute_id' => $dispute->id,
            'refunded_at' => $resolvedAt->toISOString(),
        ]);
    }

    private function returnToRevision(Order $order, Dispute $dispute, User $user, OrderEventLogger $events, mixed $resolvedAt): void
    {
        $order->submissions()->latest()->first()?->update([
            'status' => OrderSubmission::STATUS_REVISION_REQUESTED,
        ]);

        $order->update([
            'status' => Order::STATUS_REVISION_REQUESTED,
            'payment_status' => Order::PAYMENT_HELD,
            'review_hold_started_at' => null,
            'review_hold_until' => null,
            'auto_release_at' => null,
        ]);

        $events->disputeResolved($order, $user, [
            'dispute_id' => $dispute->id,
            'resolution' => Dispute::RESOLUTION_RETURN_TO_REVISION,
        ]);

        $events->revisionRequestedByModerator($order, $user, [
            'dispute_id' => $dispute->id,
            'resolved_at' => $resolvedAt->toISOString(),
        ]);
    }

    /**
     * @param  array<string, string>  $labels
     * @return array<int, array{value: string, label: string}>
     */
    private function optionPayload(array $labels): array
    {
        return collect($labels)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function disputePayload(Dispute $dispute): array
    {
        return [
            'id' => $dispute->id,
            'status' => $dispute->status,
            'status_label' => Dispute::statusLabels()[$dispute->status] ?? $dispute->status,
            'reason' => $dispute->reason,
            'reason_label' => Dispute::reasonLabels()[$dispute->reason] ?? $dispute->reason,
            'description' => $dispute->description,
            'resolution' => $dispute->resolution,
            'resolution_label' => $dispute->resolution ? (Dispute::resolutionLabels()[$dispute->resolution] ?? $dispute->resolution) : null,
            'moderator_comment' => $dispute->moderator_comment,
            'resolved_at' => $dispute->resolved_at?->format('d.m.Y H:i'),
            'opened_by' => $this->userPayload($dispute->openedBy),
            'resolved_by' => $this->userPayload($dispute->resolvedBy),
            'created_at' => $dispute->created_at?->format('d.m.Y H:i'),
            'order' => $this->orderPayload($dispute->order),
            'messages' => $dispute->messages->map(fn (DisputeMessage $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'is_system' => $message->is_system,
                'author' => $message->user?->name ?? 'Система',
                'author_role' => $message->is_system ? 'Система' : $this->orderRoleLabel($message->user, $dispute->order),
                'created_at' => $message->created_at?->format('d.m.Y H:i'),
            ]),
            'materials' => $this->materialsPayload($dispute->order),
            'take_url' => route('moderator.disputes.take', $dispute),
            'resolve_url' => route('moderator.disputes.resolve', $dispute),
            'message_url' => route('moderator.disputes.messages.store', $dispute),
            'can_take' => Gate::allows('take', $dispute),
            'can_resolve' => Gate::allows('resolve', $dispute),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'title' => $order->title,
            'description' => $order->description,
            'status' => $order->status,
            'status_label' => $this->statusLabels()[$order->status] ?? $order->status,
            'payment_status' => $order->payment_status,
            'payment_status_label' => $this->paymentStatusLabels()[$order->payment_status] ?? $order->payment_status,
            'price' => $order->price,
            'performer_amount' => $order->performer_amount,
            'customer' => $this->userPayload($order->customer),
            'performer' => $this->userPayload($order->performer),
            'source' => [
                'type' => $order->source_type,
                'service_title' => $order->service?->title,
                'task_title' => $order->task?->title,
                'task_offer_id' => $order->task_offer_id,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function materialsPayload(Order $order): array
    {
        return [
            'messages' => $order->orderMessages->map(fn (OrderMessage $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'author' => $message->user?->name ?? 'Система',
                'author_role' => $this->orderRoleLabel($message->user, $order),
                'created_at' => $message->created_at?->format('d.m.Y H:i'),
            ]),
            'files' => $order->orderFiles->map(fn (OrderFile $file): array => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'author' => $file->user?->name,
                'author_role' => $this->orderRoleLabel($file->user, $order),
                'size' => $file->size,
                'created_at' => $file->created_at?->format('d.m.Y H:i'),
            ]),
            'events' => $order->orderEvents->map(fn (OrderEvent $event): array => [
                'id' => $event->id,
                'type' => $event->type,
                'actor' => $event->user?->name ?? 'Система',
                'created_at' => $event->created_at?->format('d.m.Y H:i'),
            ]),
            'submissions' => $order->submissions->map(fn (OrderSubmission $submission): array => [
                'id' => $submission->id,
                'message' => $submission->message,
                'status' => $submission->status,
                'author' => $submission->user?->name,
                'created_at' => $submission->created_at?->format('d.m.Y H:i'),
            ]),
        ];
    }

    /**
     * @return array{id: int|null, name: string|null, role: string|null}
     */
    private function userPayload(?User $user): array
    {
        return [
            'id' => $user?->id,
            'name' => $user?->name,
            'role' => $user?->role,
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

        return $user->isAdmin() ? 'Администратор' : 'Модератор';
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
}
