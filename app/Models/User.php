<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'performer_rating', 'performer_reviews_count', 'performer_completed_orders_count'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_PERFORMER = 'performer';

    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_ADMIN = 'admin';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'performer_rating' => 'decimal:2',
            'performer_reviews_count' => 'integer',
            'performer_completed_orders_count' => 'integer',
        ];
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function isPerformer(): bool
    {
        return $this->role === self::ROLE_PERFORMER;
    }

    public function isModerator(): bool
    {
        return $this->role === self::ROLE_MODERATOR;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function dashboardPath(): string
    {
        return match ($this->role) {
            self::ROLE_PERFORMER => '/performer/dashboard',
            self::ROLE_MODERATOR => '/moderator/dashboard',
            self::ROLE_ADMIN => '/admin/dashboard',
            default => '/customer/dashboard',
        };
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function performerProfile(): HasOne
    {
        return $this->hasOne(PerformerProfile::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function taskOffers(): HasMany
    {
        return $this->hasMany(TaskOffer::class);
    }

    public function taskFavorites(): HasMany
    {
        return $this->hasMany(TaskFavorite::class);
    }

    public function favoriteCategories(): HasMany
    {
        return $this->hasMany(PerformerFavoriteCategory::class);
    }

    public function favoriteTaskTypes(): HasMany
    {
        return $this->hasMany(PerformerFavoriteTaskType::class);
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function performerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'performer_id');
    }

    public function givenReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'performer_id');
    }

    public function orderMessages(): HasMany
    {
        return $this->hasMany(OrderMessage::class);
    }

    public function orderFiles(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }

    public function orderEvents(): HasMany
    {
        return $this->hasMany(OrderEvent::class);
    }

    public function openedDisputes(): HasMany
    {
        return $this->hasMany(Dispute::class, 'opened_by');
    }

    public function resolvedDisputes(): HasMany
    {
        return $this->hasMany(Dispute::class, 'resolved_by');
    }

    public function verifiedPerformerProfiles(): HasMany
    {
        return $this->hasMany(PerformerProfile::class, 'verified_by');
    }

    public function disputeMessages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class);
    }

    public function paymentOperations(): HasMany
    {
        return $this->hasMany(PaymentOperation::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class, 'performer_id');
    }

    public function betaFeedback(): HasMany
    {
        return $this->hasMany(BetaFeedback::class);
    }
}
