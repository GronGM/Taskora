<?php

namespace App\Models;

use Database\Factories\OrderMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'user_id', 'body', 'type'])]
class OrderMessage extends Model
{
    /** @use HasFactory<OrderMessageFactory> */
    use HasFactory;

    public const TYPE_USER_MESSAGE = 'user_message';

    public const TYPE_SYSTEM_MESSAGE = 'system_message';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
