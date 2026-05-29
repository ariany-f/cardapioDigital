<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MarketingLead extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'restaurant_name',
        'contact_name',
        'email',
        'phone',
        'city',
        'message',
        'status',
        'internal_notes',
        'contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Nova',
            self::STATUS_CONTACTED => 'Em contato',
            self::STATUS_ARCHIVED => 'Arquivada',
        ];
    }
}
