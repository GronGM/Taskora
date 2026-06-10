<?php

namespace App\Models;

use Database\Factories\PerformerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'display_name',
    'headline',
    'bio',
    'experience_years',
    'response_time_label',
    'avatar_path',
    'cover_path',
    'portfolio_summary',
    'verification_status',
    'verification_note',
    'verified_at',
    'verified_by',
    'submitted_for_verification_at',
    'published_at',
    'is_public',
])]
class PerformerProfile extends Model
{
    /** @use HasFactory<PerformerProfileFactory> */
    use HasFactory;

    public const STATUS_NOT_SUBMITTED = 'not_submitted';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'verified_at' => 'datetime',
            'submitted_for_verification_at' => 'datetime',
            'published_at' => 'datetime',
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function specializations(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_performer_profile')
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PerformerPortfolioItem::class)->orderBy('sort_order')->latest();
    }

    public function publishedPortfolioItems(): HasMany
    {
        return $this->portfolioItems()
            ->where('status', PerformerPortfolioItem::STATUS_PUBLISHED)
            ->where('is_public', true);
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null);
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null);
    }
}
