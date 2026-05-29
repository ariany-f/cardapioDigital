<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariationOption extends Model
{
    use \App\Traits\BelongsToTenant;

    protected $fillable = ['tenant_id', 'product_variation_group_id', 'name', 'additional_price', 'max_quantity', 'sort_order'];

    protected function casts(): array
    {
        return ['additional_price' => 'decimal:2'];
    }

    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ProductVariationGroup::class, 'product_variation_group_id');
    }
}
