<?php

namespace App\Models;

use Database\Factories\TaskOfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['task_id', 'user_id', 'message', 'price', 'delivery_days', 'status'])]
class TaskOffer extends Model
{
    /** @use HasFactory<TaskOfferFactory> */
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ACCEPTED = 'accepted';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'delivery_days' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }
}
