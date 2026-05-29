<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use \App\Traits\BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'branch_id', 'code', 'type', 'value', 'min_order_amount',
        'max_uses', 'uses_count', 'valid_from', 'valid_until', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
        ];
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
