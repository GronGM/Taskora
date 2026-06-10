<?php

namespace App\Http\Controllers\Performer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Performer\SubmitWorkRequest;
use App\Models\Order;
use App\Models\OrderSubmission;
use App\Services\Orders\OrderEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PerformerOrderController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewPerformerAny', Order::class);

        $orders = request()->user()
            ->performerOrders()
            ->with(['customer', 'service', 'task'])
            ->latest()
            ->get()
            ->map(fn (Order $order): array => $this->orderCard($order));

        return Inertia::render('Performer/Orders/Index', [
            'orders' => $orders,
            'statusLabels' => $this->statusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
        ]);
    }

    public function show(Order $order): Response
    {
        Gate::authorize('viewAsPerformer', $order);

        $order->load(['customer', 'category', 'service', 'task', 'taskOffer', 'submissions.user']);

        return Inertia::render('Performer/Orders/Show', [
            'order' => $this->orderDetail($order),
            'statusLabels' => $this->statusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
        ]);
    }

    public function submitWork(SubmitWorkRequest $request, Order $order, OrderEventLogger $events): RedirectResponse
    {
        DB::transaction(function () use ($request, $order, $events): void {
            $submission = $order->submissions()->create([
                'user_id' => $request->user()->id,
                'message' => $request->validated('message'),
                'status' => OrderSubmission::STATUS_SUBMITTED,
            ]);

            $order->update([
                'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
                'submitted_at' => now(),
            ]);

            $events->workSubmitted($order, $request->user(), [
                'submission_id' => $submission->id,
                'status' => Order::STATUS_SUBMITTED_FOR_REVIEW,
            ]);
        });

        return redirect()
            ->route('performer.orders.show', $order)
            ->with('success', 'Работа отправлена заказчику на проверку.');
    }

    public function cancel(Order $order, OrderEventLogger $events): RedirectResponse
    {
        Gate::authorize('cancelAsPerformer', $order);
        abort_if($order->submissions()->exists(), 403);
        $user = request()->user();

        DB::transaction(function () use ($order, $events, $user): void {
            $order->update([
                'status' => Order::STATUS_CANCELED,
                'payment_status' => Order::PAYMENT_CANCELED,
                'canceled_at' => now(),
            ]);

            $events->orderCanceled($order, $user, [
                'canceled_by' => 'performer',
                'status' => Order::STATUS_CANCELED,
            ]);
        });

        return redirect()
            ->route('performer.orders.show', $order)
            ->with('success', 'Заказ отменен исполнителем.');
    }

    private function orderCard(Order $order): array
    {
        return [
            'id' => $order->id,
            'title' => $order->title,
            'source_type' => $order->source_type,
            'source_label' => $order->source_type === Order::SOURCE_SERVICE ? 'Услуга' : 'Задание',
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'price' => $order->price,
            'performer_amount' => $order->performer_amount,
            'delivery_days' => $order->delivery_days,
            'participant' => $order->customer?->name,
            'show_url' => route('performer.orders.show', $order),
            'workspace_url' => route('performer.orders.workspace', $order),
        ];
    }

    private function orderDetail(Order $order): array
    {
        return [
            ...$this->orderCard($order),
            'description' => $order->description,
            'platform_fee_percent' => $order->platform_fee_percent,
            'platform_fee_amount' => $order->platform_fee_amount,
            'started_at' => $order->started_at?->format('d.m.Y H:i'),
            'submitted_at' => $order->submitted_at?->format('d.m.Y H:i'),
            'completed_at' => $order->completed_at?->format('d.m.Y H:i'),
            'canceled_at' => $order->canceled_at?->format('d.m.Y H:i'),
            'submit_work_url' => route('performer.orders.submit-work', $order),
            'cancel_url' => route('performer.orders.cancel', $order),
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
