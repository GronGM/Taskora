<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProviderWebhookEvent;
use App\Services\Orders\OrderEventLogger;
use App\Services\Payments\PaymentLedgerService;
use App\Services\Payments\TBankClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TBankWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        TBankClient $client,
        PaymentLedgerService $ledger,
        OrderEventLogger $events,
    ): Response {
        $payload = $request->all();
        $status = (string) ($payload['Status'] ?? '');
        $paymentId = (string) ($payload['PaymentId'] ?? '');

        // Подпись обязательна: уведомление без валидного токена не обрабатывается.
        if ($paymentId === '' || ! $client->isValidNotification($payload)) {
            return response('INVALID TOKEN', 403);
        }

        $webhookEvent = ProviderWebhookEvent::query()->firstOrCreate(
            [
                'provider' => 'tbank',
                'event_id' => "{$status}:{$paymentId}",
            ],
            [
                'event_type' => $status,
                'status' => ProviderWebhookEvent::STATUS_RECEIVED,
                'payload' => collect($payload)->except('Token')->all(),
            ],
        );

        if (! $webhookEvent->wasRecentlyCreated && $webhookEvent->status === ProviderWebhookEvent::STATUS_PROCESSED) {
            return response('OK', 200);
        }

        if ($status !== 'CONFIRMED') {
            $webhookEvent->update(['status' => ProviderWebhookEvent::STATUS_IGNORED, 'processed_at' => now()]);

            return response('OK', 200);
        }

        try {
            // Статус и сумму перепроверяем прямым запросом к API.
            $state = $client->getState($paymentId);
        } catch (Throwable $exception) {
            $webhookEvent->update([
                'status' => ProviderWebhookEvent::STATUS_FAILED,
                'error_message' => 'Не удалось проверить платеж через GetState.',
            ]);

            return response('RETRY', 500);
        }

        $orderId = TBankClient::orderIdFromNotification((string) ($payload['OrderId'] ?? ''));
        $order = $orderId ? Order::query()->find($orderId) : null;

        if (($state['Status'] ?? '') !== 'CONFIRMED' || ! $order) {
            $webhookEvent->update(['status' => ProviderWebhookEvent::STATUS_IGNORED, 'processed_at' => now()]);

            return response('OK', 200);
        }

        if ((int) ($state['Amount'] ?? 0) !== (int) $order->price * 100) {
            $webhookEvent->update([
                'status' => ProviderWebhookEvent::STATUS_FAILED,
                'error_message' => 'Сумма платежа не совпадает с суммой заказа.',
            ]);

            return response('OK', 200);
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
            $ledger->recordTBankHold($fresh, $fresh->customer, $paymentId);

            $events->paymentConfirmed($fresh, null, [
                'payment_status' => Order::PAYMENT_HELD,
                'status' => Order::STATUS_IN_PROGRESS,
                'provider' => 'tbank',
            ]);
        });

        $webhookEvent->update(['status' => ProviderWebhookEvent::STATUS_PROCESSED, 'processed_at' => now()]);

        return response('OK', 200);
    }
}
