<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Services\Orders\OrderEventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceOrderController extends Controller
{
    public function store(Request $request, Service $service, OrderEventLogger $events): RedirectResponse
    {
        abort_unless($request->user()?->isCustomer(), 403);
        abort_unless($service->status === Service::STATUS_PUBLISHED, 404);
        abort_if($service->user_id === $request->user()->id, 403);

        $service->load(['category', 'packages']);
        $package = $this->selectedPackage($request, $service);
        $reviewHoldDays = $this->reviewHoldDays($request);

        $order = DB::transaction(function () use ($request, $service, $package, $events, $reviewHoldDays): Order {
            $price = $package?->price ?? $service->price_from;
            $deliveryDays = $package?->delivery_days ?? $service->delivery_days;
            $feePercent = $this->feePercent();
            $feeAmount = (int) round($price * $feePercent / 100);

            $order = Order::create([
                'customer_id' => $request->user()->id,
                'performer_id' => $service->user_id,
                'category_id' => $service->category_id,
                'service_id' => $service->id,
                'source_type' => Order::SOURCE_SERVICE,
                'title' => $service->title,
                'description' => $package?->description ?: $service->description,
                'price' => $price,
                'delivery_days' => $deliveryDays,
                'platform_fee_percent' => $feePercent,
                'platform_fee_amount' => $feeAmount,
                'performer_amount' => $price - $feeAmount,
                'status' => Order::STATUS_AWAITING_PAYMENT,
                'payment_status' => Order::PAYMENT_UNPAID,
                'review_hold_days' => $reviewHoldDays,
            ]);

            $events->orderCreated($order, $request->user(), [
                'source_type' => Order::SOURCE_SERVICE,
                'service_id' => $service->id,
                'package_id' => $package?->id,
            ]);

            return $order;
        });

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', 'Заказ создан. Для продолжения используйте локальную заглушку оплаты.');
    }

    private function selectedPackage(Request $request, Service $service): ?ServicePackage
    {
        if ($service->packages->isEmpty()) {
            return null;
        }

        $request->validate([
            'package_id' => ['required', 'integer'],
        ]);

        return $service->packages
            ->firstWhere('id', (int) $request->integer('package_id'))
            ?? abort(422, 'Выберите пакет услуги.');
    }

    private function feePercent(): float
    {
        return (float) env('TASKORA_PLATFORM_FEE_PERCENT', 15);
    }

    private function reviewHoldDays(Request $request): int
    {
        // TODO: Add customer-facing review period selection in the service order flow.
        $request->validate([
            'review_hold_days' => [
                'nullable',
                'integer',
                'min:'.Order::REVIEW_HOLD_MIN_DAYS,
                'max:'.Order::REVIEW_HOLD_MAX_DAYS,
            ],
        ]);

        return (int) ($request->integer('review_hold_days') ?: Order::REVIEW_HOLD_DEFAULT_DAYS);
    }
}
