<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use \App\Traits\BelongsToTenant;

    protected $fillable = ['tenant_id', 'branch_id', 'name', 'type', 'rules', 'delivery_fee', 'min_order_override', 'is_active'];

    protected function casts(): array
    {
        return ['rules' => 'array', 'delivery_fee' => 'decimal:2', 'is_active' => 'boolean'];
    }
}
