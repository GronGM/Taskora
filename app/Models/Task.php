<?php

namespace App\Models;

use Database\Factories\TaskFactory;
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
    'description',
    'budget_min',
    'budget_max',
    'deadline_at',
    'status',
    'offers_count',
    'views_count',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget_min' => 'integer',
            'budget_max' => 'integer',
            'deadline_at' => 'date',
            'offers_count' => 'integer',
            'views_count' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(TaskOffer::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(TaskFile::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => "/tasks/{$this->slug}");
    }
}
