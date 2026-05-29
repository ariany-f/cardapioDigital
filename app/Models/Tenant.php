<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $fillable = [
        'name', 'legal_name', 'document_type', 'document_number', 'state_registration', 'municipal_registration',
        'slug', 'logo_path', 'phone', 'email', 'website', 'status', 'suspended_at', 'suspension_reason',
        'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'postal_code',
        'default_locale', 'currency', 'timezone', 'public_description', 'cover_image_path',
        'whatsapp', 'social_links', 'theme_primary_color', 'theme_secondary_color', 'settings_json', 'seo_json',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'settings_json' => 'array',
            'seo_json' => 'array',
            'suspended_at' => 'datetime',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(TenantSubscription::class)->where('status', 'active')->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
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

    public function formattedDocument(): ?string
    {
        if (! $this->document_number) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->document_number);

        if ($this->document_type === 'cpf' && strlen($digits) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits);
        }

        if (strlen($digits) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digits);
        }

        return $this->document_number;
    }
}
