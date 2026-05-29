<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupportRequest extends Model
{
    use \App\Traits\BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'customer_id', 'guest_name', 'guest_phone', 'guest_email',
        'order_id', 'type', 'subject', 'message',
        'status', 'admin_notes', 'closed_at', 'closed_by',
        'last_responded_at', 'last_responded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
            'last_responded_at' => 'datetime',
        ];
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    public function lastRespondedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_responded_by_user_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
