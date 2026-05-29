<?php

namespace App\Models;

use App\Support\MotoboyBranchAccess;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Motoboy extends Authenticatable
{
    use BelongsToTenant;

    protected $hidden = ['password'];

    public const VEHICLE_TYPES = ['motorcycle', 'bicycle', 'car', 'van', 'on_foot'];

    public const EMPLOYMENT_TYPES = ['clt', 'pj', 'freelancer', 'partner'];

    public const PIX_KEY_TYPES = ['cpf', 'cnpj', 'email', 'phone', 'random'];

    public const OPERATIONAL_STATUSES = ['available', 'busy', 'offline', 'on_break'];

    public const ACTIVE_DELIVERY_STATUSES = ['assigned', 'picked_up', 'on_route'];

    protected $fillable = [
        'tenant_id', 'name', 'phone', 'cpf', 'email', 'password', 'document_rg', 'birth_date',
        'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'postal_code',
        'emergency_contact_name', 'emergency_contact_phone',
        'vehicle', 'vehicle_type', 'license_plate', 'cnh_number', 'cnh_category', 'cnh_expires_at',
        'pix_key_type', 'pix_key', 'employment_type', 'employee_code', 'hired_at', 'commission_percent',
        'operational_status', 'max_active_deliveries', 'notes', 'is_active', 'uses_app', 'access_all_branches',
    ];

    protected $appends = ['whatsapp_url', 'full_address', 'has_app_login'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'uses_app' => 'boolean',
            'access_all_branches' => 'boolean',
            'birth_date' => 'date',
            'cnh_expires_at' => 'date',
            'hired_at' => 'date',
            'commission_percent' => 'decimal:2',
            'max_active_deliveries' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function reports(): HasMany
    {
        return $this->hasMany(MotoboyReport::class);
    }

    /** @deprecated Use branches() — mantido só para dados legados */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function usesApp(): bool
    {
        return (bool) $this->uses_app;
    }

    public function servesAllBranches(): bool
    {
        return MotoboyBranchAccess::hasUnrestrictedAccess($this);
    }

    public function getHasAppLoginAttribute(): bool
    {
        if (! $this->usesApp() || ! filled($this->email)) {
            return false;
        }

        return filled($this->getRawOriginal('password'));
    }

    public function hasCapacityForAnotherDelivery(): bool
    {
        if (! $this->usesApp()) {
            return true;
        }

        $active = $this->deliveries()
            ->whereIn('status', self::ACTIVE_DELIVERY_STATUSES)
            ->count();

        return $active < $this->max_active_deliveries;
    }

    protected function whatsappUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            $digits = preg_replace('/\D/', '', (string) $this->phone);
            if (strlen($digits) < 10) {
                return null;
            }
            if (! str_starts_with($digits, '55')) {
                $digits = '55'.$digits;
            }

            return 'https://wa.me/'.$digits;
        });
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::get(function (): ?string {
            $parts = array_filter([
                $this->street,
                $this->number ? 'nº '.$this->number : null,
                $this->complement,
                $this->neighborhood,
                $this->city,
                $this->state,
                $this->postal_code,
            ]);

            return $parts ? implode(', ', $parts) : null;
        });
    }
}
