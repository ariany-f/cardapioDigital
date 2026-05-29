<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    public const SENDER_CUSTOMER = 'customer';

    public const SENDER_STAFF = 'staff';

    protected $fillable = [
        'conversation_id', 'sender_type', 'sender_user_id', 'sender_customer_id', 'body',
        'read_at_staff', 'read_at_customer',
    ];

    protected function casts(): array
    {
        return [
            'read_at_staff' => 'datetime',
            'read_at_customer' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function senderCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'sender_customer_id');
    }
}
