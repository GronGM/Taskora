<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'customer_id',
    'performer_id',
    'category_id',
    'service_id',
    'task_id',
    'task_offer_id',
    'source_type',
    'title',
    'description',
    'price',
    'delivery_days',
    'platform_fee_percent',
    'platform_fee_amount',
    'performer_amount',
    'status',
    'payment_status',
    'review_hold_days',
    'review_hold_started_at',
    'review_hold_until',
    'auto_release_at',
    'released_at',
    'release_reason',
    'started_at',
    'submitted_at',
    'completed_at',
    'canceled_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const SOURCE_SERVICE = 'service';

    public const SOURCE_TASK_OFFER = 'task_offer';

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED_FOR_REVIEW = 'submitted_for_review';

    public const STATUS_REVISION_REQUESTED = 'revision_requested';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DISPUTED = 'disputed';

    public const STATUS_CANCELED = 'canceled';

    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_HELD = 'held';

    public const PAYMENT_RELEASED = 'released';

    public const PAYMENT_REFUNDED = 'refunded';

    public const PAYMENT_CANCELED = 'canceled';

    public const REVIEW_HOLD_DEFAULT_DAYS = 10;

    public const REVIEW_HOLD_MIN_DAYS = 5;

    public const REVIEW_HOLD_MAX_DAYS = 40;

    public const RELEASE_CUSTOMER_EARLY_ACCEPT = 'customer_early_accept';

    public const RELEASE_AUTO = 'auto_release';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'delivery_days' => 'integer',
            'platform_fee_percent' => 'decimal:2',
            'platform_fee_amount' => 'integer',
            'performer_amount' => 'integer',
            'review_hold_days' => 'integer',
            'review_hold_started_at' => 'datetime',
            'review_hold_until' => 'datetime',
            'auto_release_at' => 'datetime',
            'released_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function taskOffer(): BelongsTo
    {
        return $this->belongsTo(TaskOffer::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(OrderSubmission::class)->latest();
    }

    public function orderMessages(): HasMany
    {
        return $this->hasMany(OrderMessage::class)->oldest();
    }

    public function orderFiles(): HasMany
    {
        return $this->hasMany(OrderFile::class)->latest();
    }

    public function orderEvents(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->oldest();
    }
}
