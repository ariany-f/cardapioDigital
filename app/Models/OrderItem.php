<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use \App\Traits\BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'order_id', 'product_id', 'name', 'quantity',
        'unit_price', 'total_price', 'variations_snapshot', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'variations_snapshot' => 'array',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }
}
