<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'branch_id', 'name', 'sort_order', 'is_active', 'is_paused'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_paused' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
