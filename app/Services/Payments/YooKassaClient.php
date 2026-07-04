<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Минимальный клиент API ЮKassa (v3) для приема платежей.
 *
 * Секретный ключ берется только из конфигурации окружения и никогда
 * не логируется. Суммы передаются строкой в рублях с двумя знаками.
 */
class YooKassaClient
{
    private const BASE_URL = 'https://api.yookassa.ru/v3';

    /**
     * @return array<string, mixed>
     */
    public function createPayment(Order $order, string $idempotenceKey, string $returnUrl): array
    {
        $response = Http::withBasicAuth($this->shopId(), $this->secretKey())
            ->withHeaders(['Idempotence-Key' => $idempotenceKey])
            ->acceptJson()
            ->post(self::BASE_URL.'/payments', [
                'amount' => [
                    'value' => number_format((float) $order->price, 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'capture' => true,
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => $returnUrl,
                ],
                'description' => "Заказ №{$order->id} на Таскоре",
                'metadata' => [
                    'order_id' => (string) $order->id,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('ЮKassa: не удалось создать платеж (HTTP '.$response->status().').');
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayment(string $paymentId): array
    {
        $response = Http::withBasicAuth($this->shopId(), $this->secretKey())
            ->acceptJson()
            ->get(self::BASE_URL.'/payments/'.$paymentId);

        if (! $response->successful()) {
            throw new RuntimeException('ЮKassa: не удалось получить платеж (HTTP '.$response->status().').');
        }

        return $response->json();
    }

    private function shopId(): string
    {
        return (string) config('payments.yookassa.shop_id');
    }

    private function secretKey(): string
    {
        return (string) config('payments.yookassa.secret_key');
    }
}
