<?php

namespace App\Models;

use Database\Factories\OrderFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'user_id',
    'original_name',
    'stored_name',
    'path',
    'disk',
    'mime_type',
    'size',
    'status',
    'moderation_status',
])]
class OrderFile extends Model
{
    /** @use HasFactory<OrderFileFactory> */
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_DELETED = 'deleted';

    public const MODERATION_CLEAN = 'clean';

    public const MODERATION_FLAGGED = 'flagged';

    public const MODERATION_PENDING_REVIEW = 'pending_review';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
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
}
