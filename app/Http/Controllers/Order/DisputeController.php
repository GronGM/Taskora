<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreDisputeRequest;
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

class DisputeController extends Controller
{
    public function create(Request $request, Order $order): Response
    {
        Gate::authorize('create', [Dispute::class, $order]);

        $role = $request->user()->isCustomer() ? 'customer' : 'performer';
        $order->load(['customer', 'performer', 'activeDispute']);

        return Inertia::render('Disputes/Create', [
            'role' => $role,
            'order' => $this->orderPayload($order, $role),
            'reasonOptions' => $this->optionPayload(Dispute::reasonLabels()),
            'storeUrl' => route("{$role}.orders.disputes.store", $order),
        ]);
    }

    public function store(StoreDisputeRequest $request, Order $order, OrderEventLogger $events): RedirectResponse
    {
        $user = $request->user();
        $role = $user->isCustomer() ? 'customer' : 'performer';
        $validated = $request->validated();
        $dispute = null;

        DB::transaction(function () use ($order, $user, $validated, $events, &$dispute): void {
            $previousOrderStatus = $order->status;
            $previousPaymentStatus = $order->payment_status;

            $dispute = $order->disputes()->create([
                'opened_by' => $user->id,
                'status' => Dispute::STATUS_OPEN,
                'reason' => $validated['reason'],
                'description' => $validated['description'],
                'previous_order_status' => $previousOrderStatus,
                'previous_payment_status' => $previousPaymentStatus,
            ]);

            $order->update([
                'status' => Order::STATUS_DISPUTED,
                'payment_status' => Order::PAYMENT_HELD,
                'review_hold_until' => null,
                'auto_release_at' => null,
            ]);

            $events->disputeOpened($order, $user, [
                'dispute_id' => $dispute->id,
                'reason' => $dispute->reason,
                'previous_order_status' => $previousOrderStatus,
                'previous_payment_status' => $previousPaymentStatus,
            ]);

            $dispute->messages()->create([
                'user_id' => $user->id,
                'body' => 'Спор открыт. Автоматическая разблокировка оплаты остановлена до решения модератора.',
                'is_system' => true,
            ]);
        });

        return redirect()
            ->route("{$role}.disputes.show", $dispute)
            ->with('success', 'Спор открыт. Автоматическая разблокировка оплаты остановлена.');
    }

    public function show(Request $request, Dispute $dispute): Response
    {
        Gate::authorize('view', $dispute);

        $role = $request->user()->isCustomer() ? 'customer' : 'performer';
        $dispute->load([
            'openedBy',
            'resolvedBy',
            'messages.user',
            'order.customer',
            'order.performer',
            'order.orderMessages.user',
            'order.orderFiles.user',
            'order.orderEvents.user',
            'order.submissions.user',
        ]);

        return Inertia::render('Disputes/Show', [
            'role' => $role,
            'dispute' => $this->disputePayload($dispute, $role),
            'statusLabels' => Dispute::statusLabels(),
            'reasonLabels' => Dispute::reasonLabels(),
            'resolutionLabels' => Dispute::resolutionLabels(),
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
    private function disputePayload(Dispute $dispute, string $role): array
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
            'order' => $this->orderPayload($dispute->order, $role),
            'messages' => $dispute->messages->map(fn (DisputeMessage $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'is_system' => $message->is_system,
                'author' => $message->user?->name ?? 'Система',
                'author_role' => $message->is_system ? 'Система' : $this->orderRoleLabel($message->user, $dispute->order),
                'created_at' => $message->created_at?->format('d.m.Y H:i'),
            ]),
            'materials' => $this->materialsPayload($dispute->order, $role),
            'message_url' => route("{$role}.disputes.messages.store", $dispute),
        ];
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
            'status' => $order->status,
            'status_label' => $this->statusLabels()[$order->status] ?? $order->status,
            'payment_status' => $order->payment_status,
            'payment_status_label' => $this->paymentStatusLabels()[$order->payment_status] ?? $order->payment_status,
            'price' => $order->price,
            'performer_amount' => $order->performer_amount,
            'review_hold_until' => $order->review_hold_until?->format('d.m.Y H:i'),
            'customer' => $this->userPayload($order->customer),
            'performer' => $this->userPayload($order->performer),
            'show_url' => route("{$role}.orders.show", $order),
            'workspace_url' => route("{$role}.orders.workspace", $order),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function materialsPayload(Order $order, string $role): array
    {
        return [
            'messages' => $order->orderMessages->take(-10)->map(fn (OrderMessage $message): array => [
                'id' => $message->id,
                'body' => $message->body,
                'author' => $message->user?->name ?? 'Система',
                'author_role' => $this->orderRoleLabel($message->user, $order),
                'created_at' => $message->created_at?->format('d.m.Y H:i'),
            ])->values(),
            'files' => $order->orderFiles->map(fn (OrderFile $file): array => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'author' => $file->user?->name,
                'author_role' => $this->orderRoleLabel($file->user, $order),
                'size' => $file->size,
                'created_at' => $file->created_at?->format('d.m.Y H:i'),
                'download_url' => route("{$role}.orders.files.download", [$order, $file]),
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
