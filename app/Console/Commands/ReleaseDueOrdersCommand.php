<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Orders\OrderEventLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseDueOrdersCommand extends Command
{
    protected $signature = 'orders:release-due';

    protected $description = 'Release held funds for submitted orders whose review period has ended.';

    public function handle(OrderEventLogger $events): int
    {
        $released = 0;
        $now = now();

        Order::query()
            ->where('status', Order::STATUS_SUBMITTED_FOR_REVIEW)
            ->where('payment_status', Order::PAYMENT_HELD)
            ->whereNotNull('review_hold_until')
            ->where('review_hold_until', '<=', $now)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$released, $events): void {
                foreach ($orders as $order) {
                    DB::transaction(function () use ($order, &$released, $events): void {
                        $releasedAt = now();

                        $order->update([
                            'status' => Order::STATUS_COMPLETED,
                            'payment_status' => Order::PAYMENT_RELEASED,
                            'completed_at' => $releasedAt,
                            'released_at' => $releasedAt,
                            'release_reason' => Order::RELEASE_AUTO,
                        ]);

                        $events->orderCompleted($order, null, [
                            'status' => Order::STATUS_COMPLETED,
                            'payment_status' => Order::PAYMENT_RELEASED,
                            'release_reason' => Order::RELEASE_AUTO,
                        ]);

                        $events->fundsReleased($order, null, [
                            'release_reason' => Order::RELEASE_AUTO,
                            'released_at' => $releasedAt->toISOString(),
                        ]);

                        $released++;
                    });
                }
            });

        $this->info("Разблокировано заказов: {$released}");

        return self::SUCCESS;
    }
}
