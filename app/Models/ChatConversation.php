<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChatConversation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'uuid', 'tenant_id', 'branch_id', 'customer_id', 'guest_key',
        'guest_name', 'guest_phone', 'order_id', 'status', 'last_message_at',
        'staff_unread_count', 'customer_unread_count',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'staff_unread_count' => 'integer',
            'customer_unread_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChatConversation $conversation) {
            if (! $conversation->uuid) {
                $conversation->uuid = (string) Str::uuid();
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function displayName(): string
    {
        if ($this->customer) {
            return $this->customer->name;
        }

        return $this->guest_name ?: 'Cliente';
    }
}
