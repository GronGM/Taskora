<?php

namespace App\Models;

use Database\Factories\PayoutRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'performer_id',
    'amount',
    'currency',
    'status',
    'requested_at',
    'reviewed_by',
    'reviewed_at',
    'paid_at',
    'rejection_reason',
])]
class PayoutRequest extends Model
{
    /** @use HasFactory<PayoutRequestFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELED = 'canceled';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performer_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
