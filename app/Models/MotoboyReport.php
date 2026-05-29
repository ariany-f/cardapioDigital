<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotoboyReport extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'motoboy_id', 'customer_id', 'order_id', 'message',
        'status', 'admin_response', 'handled_by_user_id', 'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function motoboy(): BelongsTo
    {
        return $this->belongsTo(Motoboy::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by_user_id');
    }
}
