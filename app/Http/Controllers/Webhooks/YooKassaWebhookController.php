<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProviderWebhookEvent;
use App\Services\Orders\OrderEventLogger;
use App\Services\Payments\PaymentLedgerService;
use App\Services\Payments\YooKassaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class YooKassaWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        YooKassaClient $client,
        PaymentLedgerService $ledger,
        OrderEventLogger $events,
    ): JsonResponse {
        $eventType = (string) $request->input('event', '');
        $paymentId = (string) $request->input('object.id', '');

        if ($eventType === '' || $paymentId === '') {
            return response()->json(['status' => 'ignored'], 200);
        }

        $webhookEvent = ProviderWebhookEvent::query()->firstOrCreate(
            [
                'provider' => 'yookassa',
                'event_id' => "{$eventType}:{$paymentId}",
            ],
            [
                'event_type' => $eventType,
                'status' => ProviderWebhookEvent::STATUS_RECEIVED,
                'payload' => $request->all(),
            ],
        );

        if (! $webhookEvent->wasRecentlyCreated && $webhookEvent->status === ProviderWebhookEvent::STATUS_PROCESSED) {
            return response()->json(['status' => 'already_processed'], 200);
        }

        if ($eventType !== ProviderWebhookEvent::EVENT_PAYMENT_SUCCEEDED) {
            $webhookEvent->update(['status' => ProviderWebhookEvent::STATUS_IGNORED, 'processed_at' => now()]);

            return response()->json(['status' => 'ignored'], 200);
        }

        try {
            // Данные платежа берем из API, а не из тела запроса: подделать вебхук нельзя.
            $payment = $client->getPayment($paymentId);
        } catch (Throwable $exception) {
            $webhookEvent->update([
                'status' => ProviderWebhookEvent::STATUS_FAILED,
                'error_message' => 'Не удалось проверить платеж через API.',
            ]);

            return response()->json(['status' => 'retry'], 500);
        }

        $orderId = (int) ($payment['metadata']['order_id'] ?? 0);
        $order = $orderId > 0 ? Order::query()->find($orderId) : null;

        if (($payment['status'] ?? '') !== 'succeeded' || ! $order) {
            $webhookEvent->update(['status' => ProviderWebhookEvent::STATUS_IGNORED, 'processed_at' => now()]);

            return response()->json(['status' => 'ignored'], 200);
        }

        $paidAmount = (float) ($payment['amount']['value'] ?? 0);

        if (abs($paidAmount - (float) $order->price) > 0.009) {
            $webhookEvent->update([
                'status' => ProviderWebhookEvent::STATUS_FAILED,
                'error_message' => 'Сумма платежа не совпадает с суммой заказа.',
            ]);

            return response()->json(['status' => 'amount_mismatch'], 200);
        }

        DB::transaction(function () use ($order, $ledger, $events, $paymentId): void {
            $fresh = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status !== Order::STATUS_AWAITING_PAYMENT || $fresh->payment_status !== Order::PAYMENT_UNPAID) {
                return;
            }

            $fresh->update([
                'payment_status' => Order::PAYMENT_HELD,
                'status' => Order::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            $fresh->loadMissing('customer');
            $ledger->recordYooKassaHold($fresh, $fresh->customer, $paymentId);

            $events->paymentConfirmed($fresh, null, [
                'payment_status' => Order::PAYMENT_HELD,
                'status' => Order::STATUS_IN_PROGRESS,
                'provider' => 'yookassa',
            ]);
        });

        $webhookEvent->update(['status' => ProviderWebhookEvent::STATUS_PROCESSED, 'processed_at' => now()]);

        return response()->json(['status' => 'ok'], 200);
    }
}
