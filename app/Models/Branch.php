<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'phone', 'instagram', 'is_active', 'street', 'number', 'complement',
        'neighborhood', 'city', 'state', 'postal_code', 'latitude', 'longitude', 'opening_hours',
        'public_description', 'cover_image_path', 'pickup_available', 'delivery_available', 'delivery_radius_km',
        'print_format', 'print_formats_enabled', 'print_copies_default', 'auto_print_on_new_order',
        'minimum_order_amount', 'packaging_fee_default', 'order_disposables',
        'default_prep_time_minutes', 'delivery_time_minutes',
        'auto_accept_orders', 'allow_scheduled_orders', 'orders_status_override', 'notification_email',
    ];

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'print_formats_enabled' => 'array',
            'order_disposables' => 'array',
            'is_active' => 'boolean',
            'pickup_available' => 'boolean',
            'delivery_available' => 'boolean',
            'auto_print_on_new_order' => 'boolean',
            'auto_accept_orders' => 'boolean',
            'allow_scheduled_orders' => 'boolean',
            'minimum_order_amount' => 'decimal:2',
            'packaging_fee_default' => 'decimal:2',
            'delivery_radius_km' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class);
    }

    public function isOpenNow(): bool
    {
        return app(\App\Services\BranchHoursService::class)->isOpen($this);
    }

    public function fullAddress(): string
    {
        $line1 = collect([$this->street, $this->number])->filter()->implode(', ');

        return collect([
            $line1,
            $this->complement,
            $this->neighborhood,
            collect([$this->city, $this->state])->filter()->implode(' - '),
            $this->postal_code ? 'CEP '.$this->postal_code : null,
        ])->filter()->implode(', ');
    }
}
