<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'category_id',
    'title',
    'slug',
    'short_description',
    'description',
    'cover_path',
    'price_from',
    'delivery_days',
    'max_review_hold_days',
    'status',
    'rating',
    'reviews_count',
    'orders_count',
    'is_featured',
    'moderated_by',
    'moderated_at',
    'rejection_reason',
])]
class Service extends Model
{
    protected static function booted(): void
    {
        static::saving(function (Service $service): void {
            $service->search_title = \App\Services\Search\RelevanceSearch::normalize($service->title);
            $service->search_text = \App\Services\Search\RelevanceSearch::normalize($service->short_description, $service->description);
        });
    }

    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_from' => 'integer',
            'delivery_days' => 'integer',
            'rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'orders_count' => 'integer',
            'is_featured' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(ServicePackage::class)->orderBy('sort_order')->orderBy('price');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => "/services/{$this->slug}");
    }

    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->cover_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->cover_path)
            : null);
    }
}
