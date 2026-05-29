<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryStatusHistory extends Model
{
    protected $fillable = ['delivery_id', 'status', 'changed_by', 'origin'];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
}
