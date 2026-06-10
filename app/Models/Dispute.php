<?php

namespace App\Models;

use Database\Factories\DisputeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id',
    'opened_by',
    'resolved_by',
    'status',
    'reason',
    'description',
    'previous_order_status',
    'previous_payment_status',
    'resolution',
    'moderator_comment',
    'resolved_at',
    'canceled_at',
])]
class Dispute extends Model
{
    /** @use HasFactory<DisputeFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CANCELED = 'canceled';

    public const REASON_WORK_NOT_DELIVERED = 'work_not_delivered';

    public const REASON_POOR_QUALITY = 'poor_quality';

    public const REASON_MISSED_DEADLINE = 'missed_deadline';

    public const REASON_REQUIREMENTS_MISMATCH = 'requirements_mismatch';

    public const REASON_CUSTOMER_UNRESPONSIVE = 'customer_unresponsive';

    public const REASON_PERFORMER_UNRESPONSIVE = 'performer_unresponsive';

    public const REASON_OTHER = 'other';

    public const RESOLUTION_RELEASE_TO_PERFORMER = 'release_to_performer';

    public const RESOLUTION_REFUND_TO_CUSTOMER = 'refund_to_customer';

    public const RESOLUTION_RETURN_TO_REVISION = 'return_to_revision';

    /**
     * @return array<int, string>
     */
    public static function activeStatuses(): array
    {
        return [self::STATUS_OPEN, self::STATUS_UNDER_REVIEW];
    }

    /**
     * @return array<int, string>
     */
    public static function reasons(): array
    {
        return [
            self::REASON_WORK_NOT_DELIVERED,
            self::REASON_POOR_QUALITY,
            self::REASON_MISSED_DEADLINE,
            self::REASON_REQUIREMENTS_MISMATCH,
            self::REASON_CUSTOMER_UNRESPONSIVE,
            self::REASON_PERFORMER_UNRESPONSIVE,
            self::REASON_OTHER,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function resolutions(): array
    {
        return [
            self::RESOLUTION_RELEASE_TO_PERFORMER,
            self::RESOLUTION_REFUND_TO_CUSTOMER,
            self::RESOLUTION_RETURN_TO_REVISION,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_OPEN => 'Открыт',
            self::STATUS_UNDER_REVIEW => 'На рассмотрении',
            self::STATUS_RESOLVED => 'Решен',
            self::STATUS_CANCELED => 'Отменен',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function reasonLabels(): array
    {
        return [
            self::REASON_WORK_NOT_DELIVERED => 'Работа не сдана',
            self::REASON_POOR_QUALITY => 'Низкое качество',
            self::REASON_MISSED_DEADLINE => 'Нарушен срок',
            self::REASON_REQUIREMENTS_MISMATCH => 'Не соответствует заданию',
            self::REASON_CUSTOMER_UNRESPONSIVE => 'Заказчик не отвечает',
            self::REASON_PERFORMER_UNRESPONSIVE => 'Исполнитель не отвечает',
            self::REASON_OTHER => 'Другое',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function resolutionLabels(): array
    {
        return [
            self::RESOLUTION_RELEASE_TO_PERFORMER => 'Оплата разблокирована исполнителю',
            self::RESOLUTION_REFUND_TO_CUSTOMER => 'Средства возвращены заказчику',
            self::RESOLUTION_RETURN_TO_REVISION => 'Заказ возвращен на доработку',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class)->oldest();
    }
}
