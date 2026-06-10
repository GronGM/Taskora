<?php

namespace App\Models;

use Database\Factories\ProviderWebhookEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'provider',
    'event_id',
    'event_type',
    'status',
    'payload',
    'processed_at',
    'error_message',
])]
class ProviderWebhookEvent extends Model
{
    /** @use HasFactory<ProviderWebhookEventFactory> */
    use HasFactory;

    public const STATUS_RECEIVED = 'received';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_IGNORED = 'ignored';

    public const STATUS_FAILED = 'failed';

    public const EVENT_PAYMENT_SUCCEEDED = 'payment.succeeded';

    public const EVENT_PAYMENT_WAITING_FOR_CAPTURE = 'payment.waiting_for_capture';

    public const EVENT_PAYMENT_CANCELED = 'payment.canceled';

    public const EVENT_REFUND_SUCCEEDED = 'refund.succeeded';

    public const EVENT_PAYOUT_SUCCEEDED = 'payout.succeeded';

    /**
     * @return array<int, string>
     */
    public static function futureEventTypes(): array
    {
        return [
            self::EVENT_PAYMENT_SUCCEEDED,
            self::EVENT_PAYMENT_WAITING_FOR_CAPTURE,
            self::EVENT_PAYMENT_CANCELED,
            self::EVENT_REFUND_SUCCEEDED,
            self::EVENT_PAYOUT_SUCCEEDED,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_RECEIVED => 'Получено',
            self::STATUS_PROCESSED => 'Обработано',
            self::STATUS_IGNORED => 'Проигнорировано',
            self::STATUS_FAILED => 'Ошибка',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
