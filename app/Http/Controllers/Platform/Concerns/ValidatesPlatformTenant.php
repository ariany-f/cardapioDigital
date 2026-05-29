<?php

namespace App\Http\Controllers\Platform\Concerns;

use App\Models\Language;
use App\Models\Tenant;
use App\Support\TenantFeatures;
use App\Support\TenantRegionalOptions;
use Illuminate\Validation\Rule;

trait ValidatesPlatformTenant
{
    protected function tenantRules(?Tenant $tenant = null, bool $creating = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', Rule::in(['cnpj', 'cpf'])],
            'document_number' => ['nullable', 'string', 'max:20'],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'municipal_registration' => ['nullable', 'string', 'max:30'],
            'slug' => [
                $creating ? 'nullable' : 'required',
                'string',
                'max:255',
                Rule::unique('tenants', 'slug')->ignore($tenant?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'postal_code' => ['nullable', 'string', 'max:12'],
            'default_locale' => ['nullable', 'string', 'max:10', Rule::in($this->activeLocaleCodes())],
            'currency' => ['nullable', 'string', 'max:3', Rule::in(TenantRegionalOptions::currencyCodes())],
            'timezone' => ['nullable', 'string', 'max:50', Rule::in(TenantRegionalOptions::timezoneIds())],
            'public_description' => ['nullable', 'string'],
            'theme_primary_color' => ['nullable', 'string', 'max:20'],
            'theme_secondary_color' => ['nullable', 'string', 'max:20'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'motoboys_enabled' => array_values(array_filter([
                'sometimes',
                'boolean',
                $tenant ? function (string $attribute, mixed $value, \Closure $fail) use ($tenant): void {
                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false) {
                        return;
                    }
                    if (TenantFeatures::hasMotoboyDeliveriesInProgress($tenant)) {
                        $fail(__('tenant.features.motoboys_disable_blocked'));
                    }
                } : null,
            ])),
            'pos_enabled' => ['sometimes', 'boolean'],
            'kds_enabled' => ['sometimes', 'boolean'],
        ];

        if ($creating) {
            $rules['plan_id'] = ['required', 'exists:plans,id'];
        }

        return $rules;
    }

    protected function tenantValidationAttributes(): array
    {
        return [
            'name' => 'nome fantasia',
            'slug' => 'slug',
            'plan_id' => 'plano',
            'motoboys_enabled' => 'módulo de entregadores',
            'pos_enabled' => 'PDV',
            'kds_enabled' => 'KDS',
            'email' => 'e-mail',
            'default_locale' => 'idioma padrão',
            'currency' => 'moeda',
            'timezone' => 'fuso horário',
        ];
    }

    /**
     * @return list<string>
     */
    protected function activeLocaleCodes(): array
    {
        $codes = Language::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('code')
            ->all();

        return $codes !== [] ? $codes : ['pt_BR'];
    }

    protected function tenantAttributes(array $data): array
    {
        $attrs = collect($data)->only([
            'name', 'legal_name', 'document_type', 'document_number',
            'state_registration', 'municipal_registration', 'slug',
            'phone', 'email', 'whatsapp', 'website',
            'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'postal_code',
            'default_locale', 'currency', 'timezone', 'public_description',
            'theme_primary_color', 'theme_secondary_color',
        ])->filter(fn ($v) => $v !== null)->all();

        if (array_key_exists('instagram', $data)) {
            $links = [];
            if (! empty($data['instagram'])) {
                $links['instagram'] = $data['instagram'];
            }
            $attrs['social_links'] = $links;
        }

        return $attrs;
    }
}
