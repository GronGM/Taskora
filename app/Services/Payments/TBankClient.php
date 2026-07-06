<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Клиент eACQ Т-Банка (v2) для приема платежей.
 *
 * Суммы передаются в копейках. Каждый запрос подписывается токеном:
 * корневые параметры + Password, сортировка по ключу, конкатенация
 * значений, SHA-256 (UTF-8). Пароль терминала берется только из конфига.
 */
class TBankClient
{
    private const BASE_URL = 'https://securepay.tinkoff.ru/v2';

    /**
     * @return array<string, mixed>
     */
    public function init(Order $order, string $successUrl, string $failUrl, string $notificationUrl): array
    {
        $params = [
            'TerminalKey' => $this->terminalKey(),
            'Amount' => (int) $order->price * 100,
            'OrderId' => $this->orderId($order),
            'Description' => "Заказ №{$order->id} на Таскоре",
            'SuccessURL' => $successUrl,
            'FailURL' => $failUrl,
            'NotificationURL' => $notificationUrl,
        ];

        $params['Token'] = $this->token($params);

        $response = Http::acceptJson()->post(self::BASE_URL.'/Init', $params);

        if (! $response->successful() || ! ($response->json('Success') ?? false)) {
            throw new RuntimeException('Т-Банк: не удалось создать платеж (HTTP '.$response->status().', код '.($response->json('ErrorCode') ?? '?').').');
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function getState(string $paymentId): array
    {
        $params = [
            'TerminalKey' => $this->terminalKey(),
            'PaymentId' => $paymentId,
        ];

        $params['Token'] = $this->token($params);

        $response = Http::acceptJson()->post(self::BASE_URL.'/GetState', $params);

        if (! $response->successful() || ! ($response->json('Success') ?? false)) {
            throw new RuntimeException('Т-Банк: не удалось получить статус платежа (HTTP '.$response->status().').');
        }

        return $response->json();
    }

    /**
     * Подпись: только корневые скалярные параметры, плюс Password,
     * сортировка по ключу, конкатенация значений, SHA-256.
     *
     * @param  array<string, mixed>  $params
     */
    public function token(array $params): string
    {
        $values = [];

        foreach ($params as $key => $value) {
            if ($key === 'Token' || is_array($value) || is_object($value)) {
                continue;
            }

            $values[$key] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        $values['Password'] = $this->password();
        ksort($values);

        return hash('sha256', implode('', $values));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function isValidNotification(array $payload): bool
    {
        $token = (string) ($payload['Token'] ?? '');

        if ($token === '') {
            return false;
        }

        return hash_equals($this->token($payload), $token);
    }

    public function orderId(Order $order): string
    {
        // OrderId должен быть уникальным для каждой попытки оплаты.
        return "taskora-{$order->id}-".now()->timestamp;
    }

    public static function orderIdFromNotification(string $orderId): ?int
    {
        return preg_match('/^taskora-(\d+)-\d+$/', $orderId, $matches) === 1 ? (int) $matches[1] : null;
    }

    private function terminalKey(): string
    {
        return (string) config('payments.tbank.terminal_key');
    }

    private function password(): string
    {
        return (string) config('payments.tbank.password');
    }
}
