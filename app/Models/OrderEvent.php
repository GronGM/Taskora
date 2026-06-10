<?php

namespace App\Models;

use Database\Factories\OrderEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'user_id', 'type', 'payload'])]
class OrderEvent extends Model
{
    /** @use HasFactory<OrderEventFactory> */
    use HasFactory;

    public const TYPE_ORDER_CREATED = 'order_created';

    public const TYPE_PAYMENT_STUB_PAID = 'payment_stub_paid';

    public const TYPE_WORK_SUBMITTED = 'work_submitted';

    public const TYPE_REVISION_REQUESTED = 'revision_requested';

    public const TYPE_ORDER_COMPLETED = 'order_completed';

    public const TYPE_ORDER_CANCELED = 'order_canceled';

    public const TYPE_MESSAGE_SENT = 'message_sent';

    public const TYPE_FILE_UPLOADED = 'file_uploaded';

    public const TYPE_CONTACT_BLOCKED = 'contact_blocked';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
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
