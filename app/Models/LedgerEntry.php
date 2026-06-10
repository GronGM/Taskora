<?php

namespace App\Models;

use Database\Factories\LedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'payment_operation_id',
    'order_id',
    'user_id',
    'account',
    'direction',
    'amount',
    'currency',
    'description',
    'reference_type',
    'reference_id',
    'posted_at',
])]
class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory;

    public const ACCOUNT_CUSTOMER_PAYMENT = 'customer_payment';

    public const ACCOUNT_ESCROW = 'escrow';

    public const ACCOUNT_PERFORMER_PENDING = 'performer_pending';

    public const ACCOUNT_PERFORMER_AVAILABLE = 'performer_available';

    public const ACCOUNT_PLATFORM_FEE = 'platform_fee';

    public const ACCOUNT_CUSTOMER_REFUND = 'customer_refund';

    public const DIRECTION_DEBIT = 'debit';

    public const DIRECTION_CREDIT = 'credit';

    /**
     * @return array<string, string>
     */
    public static function accountLabels(): array
    {
        return [
            self::ACCOUNT_CUSTOMER_PAYMENT => 'Оплата заказчика',
            self::ACCOUNT_ESCROW => 'Удержание',
            self::ACCOUNT_PERFORMER_PENDING => 'Ожидает исполнителя',
            self::ACCOUNT_PERFORMER_AVAILABLE => 'Доступно исполнителю',
            self::ACCOUNT_PLATFORM_FEE => 'Комиссия платформы',
            self::ACCOUNT_CUSTOMER_REFUND => 'Возврат заказчику',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function directionLabels(): array
    {
        return [
            self::DIRECTION_DEBIT => 'Списание',
            self::DIRECTION_CREDIT => 'Начисление',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Ledger entries are immutable; create a correcting entry instead.');
        });
    }

    public function paymentOperation(): BelongsTo
    {
        return $this->belongsTo(PaymentOperation::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
