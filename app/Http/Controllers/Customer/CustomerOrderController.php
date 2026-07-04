<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\RequestRevisionRequest;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\OrderSubmission;
use App\Models\Review;
use App\Services\Orders\OrderEventLogger;
use App\Services\Payments\PaymentLedgerService;
use App\Services\Reviews\ReviewAggregateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrderController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewCustomerAny', Order::class);

        $orders = request()->user()
            ->customerOrders()
            ->with(['performer', 'service', 'task', 'review', 'activeDispute'])
            ->latest()
            ->get()
            ->map(fn (Order $order): array => $this->orderCard($order, 'customer'));

        return Inertia::render('Customer/Orders/Index', [
            'orders' => $orders,
            'statusLabels' => $this->statusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
        ]);
    }

    public function show(Order $order): Response
    {
        Gate::authorize('viewAsCustomer', $order);

        $order->load(['performer', 'category', 'service', 'task', 'taskOffer', 'review', 'submissions.user', 'activeDispute']);

        return Inertia::render('Customer/Orders/Show', [
            'order' => $this->orderDetail($order, 'customer'),
            'statusLabels' => $this->statusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
        ]);
    }

    public function markPaid(Order $order, OrderEventLogger $events, PaymentLedgerService $ledger): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        Gate::authorize('markPaid', $order);
        $user = request()->user();

        if (config('payments.mode') === 'yookassa') {
            $payment = app(\App\Services\Payments\YooKassaClient::class)->createPayment(
                $order,
                "order:{$order->id}:yookassa_payment",
                route('customer.orders.show', $order),
            );

            $confirmationUrl = $payment['confirmation']['confirmation_url'] ?? null;

            if (! is_string($confirmationUrl)) {
                return redirect()
                    ->route('customer.orders.show', $order)
                    ->with('error', 'Не удалось начать оплату. Попробуйте еще раз чуть позже.');
            }

            // Внешний redirect для Inertia: обычный redirect()->away() XHR-запрос молча проглотит.
            return Inertia::location($confirmationUrl);
        }

        $applied = DB::transaction(function () use ($order, $events, $ledger, $user): bool {
            $fresh = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status !== Order::STATUS_AWAITING_PAYMENT || $fresh->payment_status !== Order::PAYMENT_UNPAID) {
                return false;
            }

            $fresh->update([
                'payment_status' => Order::PAYMENT_HELD,
                'status' => Order::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            $ledger->recordStubHold($fresh, $user);

            $events->paymentStubPaid($fresh, $user, [
                'payment_status' => Order::PAYMENT_HELD,
                'status' => Order::STATUS_IN_PROGRESS,
            ]);

            return true;
        });

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', $applied
                ? 'Оплата отмечена локальной заглушкой. Заказ перешел в работу.'
                : 'Заказ уже переведен в работу.');
    }

    public function requestRevision(RequestRevisionRequest $request, Order $order, OrderEventLogger $events): RedirectResponse
    {
        $revisionComment = $request->validated('revision_comment');
        $user = request()->user();

        DB::transaction(function () use ($order, $events, $revisionComment, $user): void {
            $order->submissions()->latest()->first()?->update([
                'status' => OrderSubmission::STATUS_REVISION_REQUESTED,
            ]);

            $order->update([
                'status' => Order::STATUS_REVISION_REQUESTED,
                'review_hold_started_at' => null,
                'review_hold_until' => null,
                'auto_release_at' => null,
            ]);

            $events->revisionRequested($order, $user, [
                'status' => Order::STATUS_REVISION_REQUESTED,
                'revision_comment' => $revisionComment,
            ]);
        });

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', 'Запрошена доработка результата.');
    }

    public function complete(
        Order $order,
        OrderEventLogger $events,
        ReviewAggregateService $aggregates,
        PaymentLedgerService $ledger,
    ): RedirectResponse
    {
        Gate::authorize('complete', $order);
        $user = request()->user();

        DB::transaction(function () use ($order, $events, $user, $aggregates, $ledger): void {
            $releasedAt = now();

            $order->submissions()->latest()->first()?->update([
                'status' => OrderSubmission::STATUS_ACCEPTED,
            ]);

            $order->update([
                'status' => Order::STATUS_COMPLETED,
                'payment_status' => Order::PAYMENT_RELEASED,
                'completed_at' => $releasedAt,
                'released_at' => $releasedAt,
                'release_reason' => Order::RELEASE_CUSTOMER_EARLY_ACCEPT,
            ]);

            $events->orderCompleted($order, $user, [
                'status' => Order::STATUS_COMPLETED,
                'payment_status' => Order::PAYMENT_RELEASED,
            ]);

            $events->fundsReleased($order, $user, [
                'release_reason' => Order::RELEASE_CUSTOMER_EARLY_ACCEPT,
                'released_at' => $releasedAt->toISOString(),
            ]);

            $ledger->recordReleaseToPerformer($order, Order::RELEASE_CUSTOMER_EARLY_ACCEPT);

            $aggregates->recalculateForOrder($order);
        });

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', 'Работа принята, заказ завершен.');
    }

    public function cancel(Order $order, OrderEventLogger $events): RedirectResponse
    {
        Gate::authorize('cancelAsCustomer', $order);
        $user = request()->user();

        DB::transaction(function () use ($order, $events, $user): void {
            $order->update([
                'status' => Order::STATUS_CANCELED,
                'payment_status' => Order::PAYMENT_CANCELED,
                'canceled_at' => now(),
            ]);

            $events->orderCanceled($order, $user, [
                'canceled_by' => 'customer',
                'status' => Order::STATUS_CANCELED,
            ]);
        });

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', 'Заказ отменен.');
    }

    private function orderCard(Order $order, string $role): array
    {
        return [
            'id' => $order->id,
            'title' => $order->title,
            'source_type' => $order->source_type,
            'source_label' => $this->sourceLabel($order),
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'review_hold_until' => $order->review_hold_until?->format('d.m.Y H:i'),
            'released_at' => $order->released_at?->format('d.m.Y H:i'),
            'release_reason' => $order->release_reason,
            'release_reason_label' => $this->releaseReasonLabel($order->release_reason),
            'price' => $order->price,
            'performer_amount' => $order->performer_amount,
            'delivery_days' => $order->delivery_days,
            'participant' => $role === 'customer' ? $order->performer?->name : $order->customer?->name,
            'show_url' => route("{$role}.orders.show", $order),
            'workspace_url' => route("{$role}.orders.workspace", $order),
            'open_dispute_url' => route("{$role}.orders.disputes.create", $order),
            'active_dispute_url' => $order->activeDispute ? route("{$role}.disputes.show", $order->activeDispute) : null,
            'can_open_dispute' => Gate::allows('create', [Dispute::class, $order]),
            'review' => $this->reviewPayload($order->review),
            'can_review' => Gate::allows('create', [Review::class, $order]),
            'review_create_url' => $role === 'customer' ? route('customer.orders.review.create', $order) : null,
            'reviews_index_url' => $role === 'customer' ? route('customer.reviews.index') : null,
        ];
    }

    private function orderDetail(Order $order, string $role): array
    {
        return [
            ...$this->orderCard($order, $role),
            'description' => $order->description,
            'platform_fee_percent' => $order->platform_fee_percent,
            'platform_fee_amount' => $order->platform_fee_amount,
            'started_at' => $order->started_at?->format('d.m.Y H:i'),
            'submitted_at' => $order->submitted_at?->format('d.m.Y H:i'),
            'review_hold_days' => $order->review_hold_days,
            'review_hold_started_at' => $order->review_hold_started_at?->format('d.m.Y H:i'),
            'review_hold_until' => $order->review_hold_until?->format('d.m.Y H:i'),
            'auto_release_at' => $order->auto_release_at?->format('d.m.Y H:i'),
            'released_at' => $order->released_at?->format('d.m.Y H:i'),
            'release_reason' => $order->release_reason,
            'release_reason_label' => $this->releaseReasonLabel($order->release_reason),
            'completed_at' => $order->completed_at?->format('d.m.Y H:i'),
            'canceled_at' => $order->canceled_at?->format('d.m.Y H:i'),
            'mark_paid_url' => route('customer.orders.mark-paid', $order),
            'payment_mode' => (string) config('payments.mode', 'stub'),
            'request_revision_url' => route('customer.orders.request-revision', $order),
            'complete_url' => route('customer.orders.complete', $order),
            'cancel_url' => route('customer.orders.cancel', $order),
            'submissions' => $order->submissions->map(fn (OrderSubmission $submission): array => [
                'id' => $submission->id,
                'message' => $submission->message,
                'status' => $submission->status,
                'author' => $submission->user?->name,
                'created_at' => $submission->created_at?->format('d.m.Y H:i'),
            ]),
        ];
    }

    private function sourceLabel(Order $order): string
    {
        return $order->source_type === Order::SOURCE_SERVICE ? 'Услуга' : 'Задание';
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
     * @return array<string, mixed>|null
     */
    private function reviewPayload(?Review $review): ?array
    {
        if (! $review) {
            return null;
        }

        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'published_at' => $review->published_at?->format('d.m.Y H:i'),
            'show_url' => route('customer.reviews.show', $review),
        ];
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
