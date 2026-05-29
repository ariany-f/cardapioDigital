<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Delivery extends Model
{
    use BelongsToTenant;

    /** @var list<string> */
    public const TERMINAL_ORDER_STATUSES = ['delivered', 'cancelled', 'rejected'];

    protected $fillable = [
        'tenant_id', 'order_id', 'motoboy_id', 'motoboy_assignment_status',
        'motoboy_assigned_at', 'motoboy_responded_at', 'motoboy_reject_reason',
        'status', 'estimated_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_at' => 'datetime',
            'motoboy_assigned_at' => 'datetime',
            'motoboy_responded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function motoboy(): BelongsTo
    {
        return $this->belongsTo(Motoboy::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DeliveryStatusHistory::class);
    }

    /**
     * Entrega ainda ativa para o entregador (pedido não finalizado).
     */
    public function scopeInProgressForMotoboy(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereIn('status', Motoboy::ACTIVE_DELIVERY_STATUSES)
                ->orWhere('motoboy_assignment_status', 'pending');
        })->whereHas('order', fn (Builder $query) => $query->whereNotIn('status', self::TERMINAL_ORDER_STATUSES));
    }
}
