<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderSubmission;
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
            ->with(['performer', 'service', 'task'])
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

        $order->load(['performer', 'category', 'service', 'task', 'taskOffer', 'submissions.user']);

        return Inertia::render('Customer/Orders/Show', [
            'order' => $this->orderDetail($order, 'customer'),
            'statusLabels' => $this->statusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
        ]);
    }

    public function markPaid(Order $order): RedirectResponse
    {
        Gate::authorize('markPaid', $order);

        $order->update([
            'payment_status' => Order::PAYMENT_HELD,
            'status' => Order::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', 'Оплата отмечена локальной заглушкой. Заказ перешел в работу.');
    }

    public function requestRevision(Order $order): RedirectResponse
    {
        Gate::authorize('requestRevision', $order);

        DB::transaction(function () use ($order): void {
            $order->submissions()->latest()->first()?->update([
                'status' => OrderSubmission::STATUS_REVISION_REQUESTED,
            ]);

            $order->update([
                'status' => Order::STATUS_REVISION_REQUESTED,
            ]);
        });

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', 'Запрошена доработка результата.');
    }

    public function complete(Order $order): RedirectResponse
    {
        Gate::authorize('complete', $order);

        DB::transaction(function () use ($order): void {
            $order->submissions()->latest()->first()?->update([
                'status' => OrderSubmission::STATUS_ACCEPTED,
            ]);

            $order->update([
                'status' => Order::STATUS_COMPLETED,
                'payment_status' => Order::PAYMENT_RELEASED,
                'completed_at' => now(),
            ]);
        });

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', 'Работа принята, заказ завершен.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        Gate::authorize('cancelAsCustomer', $order);

        $order->update([
            'status' => Order::STATUS_CANCELED,
            'payment_status' => Order::PAYMENT_CANCELED,
            'canceled_at' => now(),
        ]);

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
            'price' => $order->price,
            'performer_amount' => $order->performer_amount,
            'delivery_days' => $order->delivery_days,
            'participant' => $role === 'customer' ? $order->performer?->name : $order->customer?->name,
            'show_url' => route("{$role}.orders.show", $order),
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
            'completed_at' => $order->completed_at?->format('d.m.Y H:i'),
            'canceled_at' => $order->canceled_at?->format('d.m.Y H:i'),
            'mark_paid_url' => route('customer.orders.mark-paid', $order),
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
            Order::PAYMENT_RELEASED => 'Выплачено исполнителю',
            Order::PAYMENT_REFUNDED => 'Возврат',
            Order::PAYMENT_CANCELED => 'Отменена',
        ];
    }
}
