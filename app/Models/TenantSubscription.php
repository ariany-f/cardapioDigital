<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscription extends Model
{
    protected $fillable = ['tenant_id', 'plan_id', 'current_period_start', 'current_period_end', 'payment_status', 'status'];

    protected function casts(): array
    {
        return [
            'current_period_start' => 'date',
            'current_period_end' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}