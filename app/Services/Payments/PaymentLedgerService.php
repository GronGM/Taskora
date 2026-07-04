<?php

namespace App\Services\Payments;

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\PaymentOperation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentLedgerService
{
    public function recordStubHold(Order $order, User $customer): PaymentOperation
    {
        return $this->recordHold(
            $order,
            $customer,
            PaymentOperation::PROVIDER_STUB,
            null,
            'Локальная заглушка оплаты: средства удержаны внутри Taskora.',
        );
    }

    public function recordYooKassaHold(Order $order, User $customer, string $providerPaymentId): PaymentOperation
    {
        return $this->recordHold(
            $order,
            $customer,
            PaymentOperation::PROVIDER_YOOKASSA,
            $providerPaymentId,
            'Оплата через ЮKassa: средства удержаны до приемки работы.',
        );
    }

    private function recordHold(Order $order, User $customer, string $provider, ?string $providerOperationId, string $description): PaymentOperation
    {
        return DB::transaction(function () use ($order, $customer, $provider, $providerOperationId, $description): PaymentOperation {
            $operation = $this->createSucceededOperation(
                order: $order,
                user: $customer,
                type: PaymentOperation::TYPE_PAYMENT_HOLD,
                amount: (int) $order->price,
                description: $description,
                payload: [
                    'payment_status' => Order::PAYMENT_HELD,
                    'provider_mode' => $provider,
                ],
                provider: $provider,
                providerOperationId: $providerOperationId,
            );

            if (! $operation->wasRecentlyCreated) {
                return $operation;
            }

            $this->addLedgerEntry(
                $operation,
                $order,
                $customer,
                LedgerEntry::ACCOUNT_CUSTOMER_PAYMENT,
                LedgerEntry::DIRECTION_DEBIT,
                (int) $order->price,
                'Заказчик оплатил заказ через локальную заглушку.',
            );
            $this->addLedgerEntry(
                $operation,
                $order,
                null,
                LedgerEntry::ACCOUNT_ESCROW,
                LedgerEntry::DIRECTION_CREDIT,
                (int) $order->price,
                'Сумма заказа удержана до приемки работы.',
            );
            $this->addLedgerEntry(
                $operation,
                $order,
                $order->performer,
                LedgerEntry::ACCOUNT_PERFORMER_PENDING,
                LedgerEntry::DIRECTION_CREDIT,
                (int) $order->performer_amount,
                'Сумма исполнителя ожидает разблокировки.',
            );
            $this->addLedgerEntry(
                $operation,
                $order,
                null,
                LedgerEntry::ACCOUNT_PLATFORM_FEE,
                LedgerEntry::DIRECTION_CREDIT,
                (int) $order->platform_fee_amount,
                'Комиссия платформы зарезервирована.',
            );

            return $operation;
        });
    }

    public function recordReleaseToPerformer(Order $order, string $reason): PaymentOperation
    {
        return DB::transaction(function () use ($order, $reason): PaymentOperation {
            $this->ensureStubHoldExists($order);

            $operation = $this->createSucceededOperation(
                order: $order,
                user: $order->performer,
                type: PaymentOperation::TYPE_RELEASE_TO_PERFORMER,
                amount: (int) $order->performer_amount,
                description: "Оплата разблокирована исполнителю: {$reason}.",
                payload: ['release_reason' => $reason],
            );

            if ($operation->wasRecentlyCreated) {
                $this->addLedgerEntry(
                    $operation,
                    $order,
                    null,
                    LedgerEntry::ACCOUNT_ESCROW,
                    LedgerEntry::DIRECTION_DEBIT,
                    (int) $order->price,
                    'Удержание закрыто после разблокировки оплаты.',
                );
                $this->addLedgerEntry(
                    $operation,
                    $order,
                    $order->performer,
                    LedgerEntry::ACCOUNT_PERFORMER_PENDING,
                    LedgerEntry::DIRECTION_DEBIT,
                    (int) $order->performer_amount,
                    'Ожидающая сумма исполнителя перенесена в доступную.',
                );
                $this->addLedgerEntry(
                    $operation,
                    $order,
                    $order->performer,
                    LedgerEntry::ACCOUNT_PERFORMER_AVAILABLE,
                    LedgerEntry::DIRECTION_CREDIT,
                    (int) $order->performer_amount,
                    'Сумма доступна исполнителю во внутреннем балансе.',
                );
            }

            $this->recordPlatformFeeCapture($order, $reason);

            return $operation;
        });
    }

    public function recordRefundToCustomer(Order $order, string $reason): PaymentOperation
    {
        return DB::transaction(function () use ($order, $reason): PaymentOperation {
            $this->ensureStubHoldExists($order);

            $operation = $this->createSucceededOperation(
                order: $order,
                user: $order->customer,
                type: PaymentOperation::TYPE_REFUND_TO_CUSTOMER,
                amount: (int) $order->price,
                description: "Возврат заказчику: {$reason}.",
                payload: ['refund_reason' => $reason],
            );

            if ($operation->wasRecentlyCreated) {
                $this->addLedgerEntry(
                    $operation,
                    $order,
                    null,
                    LedgerEntry::ACCOUNT_ESCROW,
                    LedgerEntry::DIRECTION_DEBIT,
                    (int) $order->price,
                    'Удержание закрыто из-за возврата заказчику.',
                );
                $this->addLedgerEntry(
                    $operation,
                    $order,
                    $order->customer,
                    LedgerEntry::ACCOUNT_CUSTOMER_REFUND,
                    LedgerEntry::DIRECTION_CREDIT,
                    (int) $order->price,
                    'Заказчику отражен возврат во внутренней истории.',
                );
                $this->addLedgerEntry(
                    $operation,
                    $order,
                    $order->performer,
                    LedgerEntry::ACCOUNT_PERFORMER_PENDING,
                    LedgerEntry::DIRECTION_DEBIT,
                    (int) $order->performer_amount,
                    'Ожидающая сумма исполнителя отменена из-за возврата.',
                );
            }

            if ($this->platformFeeWasReserved($order)) {
                $this->recordPlatformFeeReverse($order, $reason);
            }

            return $operation;
        });
    }

    public function recordPlatformFeeCapture(Order $order, string $reason): ?PaymentOperation
    {
        if ((int) $order->platform_fee_amount <= 0) {
            return null;
        }

        return DB::transaction(fn (): PaymentOperation => $this->createSucceededOperation(
            order: $order,
            user: null,
            type: PaymentOperation::TYPE_PLATFORM_FEE_CAPTURE,
            amount: (int) $order->platform_fee_amount,
            description: "Комиссия платформы зафиксирована: {$reason}.",
            payload: ['capture_reason' => $reason],
        ));
    }

    public function recordPlatformFeeReverse(Order $order, string $reason): ?PaymentOperation
    {
        if ((int) $order->platform_fee_amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($order, $reason): PaymentOperation {
            $operation = $this->createSucceededOperation(
                order: $order,
                user: null,
                type: PaymentOperation::TYPE_PLATFORM_FEE_REVERSE,
                amount: (int) $order->platform_fee_amount,
                description: "Комиссия платформы сторнирована: {$reason}.",
                payload: ['reverse_reason' => $reason],
            );

            if ($operation->wasRecentlyCreated) {
                $this->addLedgerEntry(
                    $operation,
                    $order,
                    null,
                    LedgerEntry::ACCOUNT_PLATFORM_FEE,
                    LedgerEntry::DIRECTION_DEBIT,
                    (int) $order->platform_fee_amount,
                    'Зарезервированная комиссия отменена из-за возврата.',
                );
            }

            return $operation;
        });
    }

    /**
     * @return array{pending_amount: int, available_amount: int, completed_orders_count: int, total_released_amount: int, platform_fee_total: int}
     */
    public function getPerformerFinanceSummary(User $performer): array
    {
        return [
            'pending_amount' => $this->accountBalance(LedgerEntry::ACCOUNT_PERFORMER_PENDING, $performer),
            'available_amount' => $this->accountBalance(LedgerEntry::ACCOUNT_PERFORMER_AVAILABLE, $performer),
            'completed_orders_count' => Order::query()
                ->where('performer_id', $performer->id)
                ->where('status', Order::STATUS_COMPLETED)
                ->where('payment_status', Order::PAYMENT_RELEASED)
                ->count(),
            'total_released_amount' => LedgerEntry::query()
                ->where('user_id', $performer->id)
                ->where('account', LedgerEntry::ACCOUNT_PERFORMER_AVAILABLE)
                ->where('direction', LedgerEntry::DIRECTION_CREDIT)
                ->sum('amount'),
            'platform_fee_total' => (int) (LedgerEntry::query()
                ->whereHas('order', fn ($query) => $query->where('performer_id', $performer->id))
                ->where('account', LedgerEntry::ACCOUNT_PLATFORM_FEE)
                ->selectRaw("coalesce(sum(case when direction = 'credit' then amount else -amount end), 0) as balance")
                ->value('balance') ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createSucceededOperation(
        Order $order,
        ?User $user,
        string $type,
        int $amount,
        string $description,
        array $payload = [],
        ?string $provider = null,
        ?string $providerOperationId = null,
    ): PaymentOperation {
        $idempotencyKey = $this->idempotencyKey($order, $type);

        return PaymentOperation::query()->firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'order_id' => $order->id,
                'user_id' => $user?->id,
                'provider' => $provider ?? PaymentOperation::PROVIDER_STUB,
                'provider_operation_id' => $providerOperationId ?? $idempotencyKey,
                'type' => $type,
                'status' => PaymentOperation::STATUS_SUCCEEDED,
                'amount' => $amount,
                'currency' => 'RUB',
                'description' => $description,
                'payload' => $payload === [] ? null : $payload,
                'succeeded_at' => now(),
            ],
        );
    }

    private function addLedgerEntry(
        PaymentOperation $operation,
        Order $order,
        ?User $user,
        string $account,
        string $direction,
        int $amount,
        string $description,
    ): void {
        if ($amount <= 0) {
            return;
        }

        $operation->ledgerEntries()->create([
            'order_id' => $order->id,
            'user_id' => $user?->id,
            'account' => $account,
            'direction' => $direction,
            'amount' => $amount,
            'currency' => 'RUB',
            'description' => $description,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'posted_at' => now(),
        ]);
    }

    private function idempotencyKey(Order $order, string $type): string
    {
        return "order:{$order->id}:{$type}";
    }

    private function accountBalance(string $account, User $user): int
    {
        return (int) (LedgerEntry::query()
            ->where('user_id', $user->id)
            ->where('account', $account)
            ->selectRaw("coalesce(sum(case when direction = 'credit' then amount else -amount end), 0) as balance")
            ->value('balance') ?? 0);
    }

    private function platformFeeWasReserved(Order $order): bool
    {
        return LedgerEntry::query()
            ->where('order_id', $order->id)
            ->where('account', LedgerEntry::ACCOUNT_PLATFORM_FEE)
            ->where('direction', LedgerEntry::DIRECTION_CREDIT)
            ->exists();
    }

    private function ensureStubHoldExists(Order $order): void
    {
        if (in_array($order->payment_status, [Order::PAYMENT_UNPAID, Order::PAYMENT_CANCELED], true)) {
            return;
        }

        if ($order->paymentOperations()->where('type', PaymentOperation::TYPE_PAYMENT_HOLD)->exists()) {
            return;
        }

        $order->loadMissing('customer');

        if ($order->customer instanceof User) {
            $this->recordStubHold($order, $order->customer);
        }
    }
}
