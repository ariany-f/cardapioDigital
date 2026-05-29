<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariationGroup extends Model
{
    use \App\Traits\BelongsToTenant;

    public const TYPE_CHOICE = 'choice';

    public const TYPE_ADDON = 'addon';

    public const TYPE_DISPOSABLE = 'disposable';

    protected $fillable = ['tenant_id', 'product_id', 'name', 'type', 'min_select', 'max_select', 'allow_quantity', 'sort_order'];

    protected function casts(): array
    {
        return ['allow_quantity' => 'boolean'];
    }

    public function options(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductVariationOption::class);
    }
}
