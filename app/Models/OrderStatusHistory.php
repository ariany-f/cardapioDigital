<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use \App\Traits\BelongsToTenant;

    protected $fillable = ['tenant_id', 'order_id', 'status', 'changed_by', 'origin', 'notes'];

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
