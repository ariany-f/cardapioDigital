<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderRating extends Model
{
    use BelongsToTenant;

    public const STATUS_APPROVED = 'approved';

    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'branch_id',
        'motoboy_id',
        'customer_id',
        'rating',
        'comment',
        'delivery_rating',
        'delivery_comment',
        'restaurant_rating',
        'restaurant_comment',
        'status',
        'moderated_at',
        'moderated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'moderated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function motoboy(): BelongsTo
    {
        return $this->belongsTo(Motoboy::class);
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by_user_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function isVisible(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function toPublicPayload(): array
    {
        return [
            'order_rating' => $this->rating,
            'order_comment' => $this->comment,
            'delivery_rating' => $this->delivery_rating,
            'delivery_comment' => $this->delivery_comment,
            'restaurant_rating' => $this->restaurant_rating,
            'restaurant_comment' => $this->restaurant_comment,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    public function toAdminPayload(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'order_rating' => $this->rating,
            'order_comment' => $this->comment,
            'delivery_rating' => $this->delivery_rating,
            'delivery_comment' => $this->delivery_comment,
            'restaurant_rating' => $this->restaurant_rating,
            'restaurant_comment' => $this->restaurant_comment,
            'created_at' => $this->created_at?->toIso8601String(),
            'moderated_at' => $this->moderated_at?->toIso8601String(),
            'order' => $this->order ? [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'type' => $this->order->type,
                'guest_name' => $this->order->guest_name,
            ] : null,
            'branch' => $this->branch?->only(['id', 'name']),
            'motoboy' => $this->motoboy?->only(['id', 'name']),
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ] : null,
            'moderated_by' => $this->moderatedBy?->only(['id', 'name']),
        ];
    }
}
