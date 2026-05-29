<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'branch_id', 'customer_id', 'table_id', 'coupon_id', 'order_number', 'type',
        'status', 'source', 'notes', 'subtotal', 'delivery_fee', 'packaging_fee', 'service_fee',
        'discount_amount', 'tip_amount', 'total', 'payment_method', 'payment_channel', 'payment_status',
        'customer_cpf', 'guest_name', 'guest_phone', 'guest_email', 'guest_access_code', 'delivery_address', 'disposables_snapshot',
        'scheduled_for', 'estimated_ready_at', 'prep_time_minutes', 'cancel_reason',
        'delivery_confirmation_code', 'delivery_confirmed_at',
        'approved_at', 'approved_by_user_id', 'cancelled_at', 'cancelled_by_user_id',
        'rejected_at', 'rejected_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'delivery_address' => 'array',
            'disposables_snapshot' => 'array',
            'scheduled_for' => 'datetime',
            'estimated_ready_at' => 'datetime',
            'delivery_confirmed_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'rejected_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'packaging_fee' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tip_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'table_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(OrderRating::class);
    }
}
