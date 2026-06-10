<?php

namespace App\Models;

use Database\Factories\PerformerPortfolioItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'performer_profile_id',
    'title',
    'description',
    'category_id',
    'image_path',
    'file_path',
    'external_url',
    'sort_order',
    'is_public',
    'status',
])]
class PerformerPortfolioItem extends Model
{
    /** @use HasFactory<PerformerPortfolioItemFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(PerformerProfile::class, 'performer_profile_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->image_path ? Storage::disk('public')->url($this->image_path) : null);
    }

    protected function fileUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->file_path ? Storage::disk('public')->url($this->file_path) : null);
    }
}
