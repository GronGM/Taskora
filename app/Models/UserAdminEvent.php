<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['target_user_id', 'actor_user_id', 'type', 'old_values', 'new_values', 'comment'])]
class UserAdminEvent extends Model
{
    use HasFactory;

    public const TYPE_ROLE_CHANGED = 'role_changed';

    public const TYPE_USER_BLOCKED = 'user_blocked';

    public const TYPE_USER_UNBLOCKED = 'user_unblocked';

    public const TYPE_ADMIN_NOTE_UPDATED = 'admin_note_updated';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
