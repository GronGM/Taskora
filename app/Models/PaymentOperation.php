<?php

namespace App\Models;

use Database\Factories\PaymentOperationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id',
    'user_id',
    'provider',
    'provider_operation_id',
    'type',
    'status',
    'amount',
    'currency',
    'idempotency_key',
    'description',
    'payload',
    'succeeded_at',
    'failed_at',
    'canceled_at',
])]
class PaymentOperation extends Model
{
    /** @use HasFactory<PaymentOperationFactory> */
    use HasFactory;

    public const PROVIDER_STUB = 'stub';

    public const PROVIDER_YOOKASSA = 'yookassa';

    public const PROVIDER_TBANK = 'tbank';

    public const TYPE_PAYMENT_HOLD = 'payment_hold';

    public const TYPE_RELEASE_TO_PERFORMER = 'release_to_performer';

    public const TYPE_REFUND_TO_CUSTOMER = 'refund_to_customer';

    public const TYPE_PLATFORM_FEE_CAPTURE = 'platform_fee_capture';

    public const TYPE_PLATFORM_FEE_REVERSE = 'platform_fee_reverse';

    public const TYPE_PAYOUT_STUB = 'payout_stub';

    public const TYPE_WEBHOOK_RECEIVED = 'webhook_received';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELED = 'canceled';

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_PAYMENT_HOLD => 'Удержание оплаты',
            self::TYPE_RELEASE_TO_PERFORMER => 'Разблокировка исполнителю',
            self::TYPE_REFUND_TO_CUSTOMER => 'Возврат заказчику',
            self::TYPE_PLATFORM_FEE_CAPTURE => 'Комиссия платформы',
            self::TYPE_PLATFORM_FEE_REVERSE => 'Возврат комиссии',
            self::TYPE_PAYOUT_STUB => 'Заглушка выплаты',
            self::TYPE_WEBHOOK_RECEIVED => 'Webhook-событие',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Ожидает',
            self::STATUS_SUCCEEDED => 'Успешно',
            self::STATUS_FAILED => 'Ошибка',
            self::STATUS_CANCELED => 'Отменено',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'payload' => 'array',
            'succeeded_at' => 'datetime',
            'failed_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
