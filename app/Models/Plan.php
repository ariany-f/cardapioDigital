<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = ['name', 'slug', 'price_monthly', 'features_json', 'is_active'];

    protected function casts(): array
    {
        return [
            'features_json' => 'array',
            'is_active' => 'boolean',
            'price_monthly' => 'decimal:2',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }
}
