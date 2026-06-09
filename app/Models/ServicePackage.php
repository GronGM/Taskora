<?php

namespace App\Models;

use Database\Factories\ServicePackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_id', 'name', 'description', 'price', 'delivery_days', 'revisions_count', 'sort_order'])]
class ServicePackage extends Model
{
    /** @use HasFactory<ServicePackageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'delivery_days' => 'integer',
            'revisions_count' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
