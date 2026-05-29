<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'category_id', 'name', 'description', 'image_path', 'base_price',
        'is_active', 'is_paused', 'tags', 'prep_time_minutes', 'track_stock', 'stock_quantity', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_active' => 'boolean',
            'is_paused' => 'boolean',
            'track_stock' => 'boolean',
            'is_featured' => 'boolean',
            'base_price' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variationGroups(): HasMany
    {
        return $this->hasMany(ProductVariationGroup::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'product_branch')
            ->withPivot(['price_override', 'is_available']);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && ! $this->is_paused
            && (! $this->track_stock || ($this->stock_quantity ?? 0) > 0);
    }
}
