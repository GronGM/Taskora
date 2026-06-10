<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Services\Notifications\NotificationService;
use App\Services\Orders\OrderEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerTaskOfferAcceptController extends Controller
{
    public function __invoke(Request $request, TaskOffer $offer, OrderEventLogger $events, NotificationService $notifications): RedirectResponse
    {
        $offer->load(['performer', 'task.category', 'order']);
        $task = $offer->task;

        abort_unless($request->user()?->isCustomer(), 403);
        abort_unless($task->user_id === $request->user()->id, 403);
        abort_unless($offer->status === TaskOffer::STATUS_SUBMITTED, 403);
        abort_if($offer->order()->exists(), 403);
        abort_if(in_array($task->status, [Task::STATUS_ARCHIVED, Task::STATUS_CLOSED], true), 403);

        $rejectedOffers = $task->offers()
            ->with('performer')
            ->whereKeyNot($offer->id)
            ->submitted()
            ->get();

        $order = DB::transaction(function () use ($offer, $task, $request, $events, $rejectedOffers): Order {
            $feePercent = $this->feePercent();
            $feeAmount = (int) round($offer->price * $feePercent / 100);

            $order = Order::create([
                'customer_id' => $task->user_id,
                'performer_id' => $offer->user_id,
                'category_id' => $task->category_id,
                'task_id' => $task->id,
                'task_offer_id' => $offer->id,
                'source_type' => Order::SOURCE_TASK_OFFER,
                'title' => $task->title,
                'description' => $offer->message ?: $task->description,
                'price' => $offer->price,
                'delivery_days' => $offer->delivery_days,
                'platform_fee_percent' => $feePercent,
                'platform_fee_amount' => $feeAmount,
                'performer_amount' => $offer->price - $feeAmount,
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'payment_status' => Order::PAYMENT_UNPAID,
                'review_hold_days' => Order::REVIEW_HOLD_DEFAULT_DAYS,
            ]);

            $offer->update(['status' => TaskOffer::STATUS_ACCEPTED]);
            $task->offers()
                ->whereKeyNot($offer->id)
                ->submitted()
                ->update(['status' => TaskOffer::STATUS_REJECTED]);
            $task->update([
                'status' => Task::STATUS_CLOSED,
                'offers_count' => 0,
            ]);

            $events->orderCreated($order, $request->user(), [
                'source_type' => Order::SOURCE_TASK_OFFER,
                'task_id' => $task->id,
                'task_offer_id' => $offer->id,
            ]);

            return $order;
        });

        $notifications->notifyUser(
            $offer->performer,
            'task_offer.accepted',
            'Отклик выбран',
            "Заказчик выбрал ваш отклик на задание «{$task->title}».",
            route('performer.offers.index'),
            [
                'actor_id' => $request->user()->id,
                'icon' => 'offer',
                'severity' => 'success',
                'related_type' => TaskOffer::class,
                'related_id' => $offer->id,
                'task_id' => $task->id,
                'order_id' => $order->id,
            ],
        );

        foreach ($rejectedOffers as $rejectedOffer) {
            $notifications->notifyUser(
                $rejectedOffer->performer,
                'task_offer.rejected',
                'Отклик отклонен',
                "Заказчик выбрал другого исполнителя для задания «{$task->title}».",
                route('performer.offers.index'),
                [
                    'actor_id' => $request->user()->id,
                    'icon' => 'offer',
                    'severity' => 'warning',
                    'related_type' => TaskOffer::class,
                    'related_id' => $rejectedOffer->id,
                    'task_id' => $task->id,
                ],
            );
        }

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', 'Исполнитель выбран, заказ создан.');
    }

    private function feePercent(): float
    {
        return (float) env('TASKORA_PLATFORM_FEE_PERCENT', 15);
    }
}
