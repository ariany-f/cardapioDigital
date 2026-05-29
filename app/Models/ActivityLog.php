<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'subject_type',
        'subject_id',
        'action',
        'description',
        'properties',
        'actor_user_id',
        'actor_customer_id',
        'actor_label',
        'origin',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'actor_customer_id');
    }
}
