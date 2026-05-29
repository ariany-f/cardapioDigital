<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Branch;
use App\Support\OrderDisposableConfig;
use Illuminate\Validation\Rule;

trait ValidatesBranch
{
    protected function branchRules(int $tenantId, ?Branch $branch = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('branches', 'slug')
                    ->where('tenant_id', $tenantId)
                    ->ignore($branch?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'postal_code' => ['nullable', 'string', 'max:12'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'public_description' => ['nullable', 'string'],
            'opening_hours' => ['nullable', 'array'],
            'pickup_available' => ['boolean'],
            'delivery_available' => ['boolean'],
            'delivery_radius_km' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'packaging_fee_default' => ['nullable', 'numeric', 'min:0'],
            'default_prep_time_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'delivery_time_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'auto_accept_orders' => ['boolean'],
            'allow_scheduled_orders' => ['boolean'],
            'auto_print_on_new_order' => ['boolean'],
            'notification_email' => ['nullable', 'email', 'max:255'],
            'print_format' => ['nullable', 'string', 'max:30'],
            'print_copies_default' => ['nullable', 'integer', 'min:1', 'max:5'],
            'order_disposables' => ['nullable', 'array'],
            'order_disposables.*.key' => ['required', 'string', 'max:64'],
            'order_disposables.*.label' => ['required', 'string', 'max:255'],
            'order_disposables.*.min_qty' => ['nullable', 'integer', 'min:0', 'max:99'],
            'order_disposables.*.max_qty' => ['nullable', 'integer', 'min:0', 'max:99'],
            'order_disposables.*.default_qty' => ['nullable', 'integer', 'min:0', 'max:99'],
        ];
    }

    protected function branchAttributes(array $data, ?string $slug = null): array
    {
        $attrs = collect($data)->only([
            'name', 'phone', 'instagram', 'is_active',
            'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'postal_code',
            'latitude', 'longitude', 'public_description', 'opening_hours',
            'pickup_available', 'delivery_available', 'delivery_radius_km',
            'minimum_order_amount', 'packaging_fee_default', 'default_prep_time_minutes', 'delivery_time_minutes',
            'auto_accept_orders', 'allow_scheduled_orders', 'auto_print_on_new_order',
            'notification_email', 'print_format', 'print_copies_default',
        ])->all();

        if (array_key_exists('order_disposables', $data)) {
            $attrs['order_disposables'] = OrderDisposableConfig::sanitizeForStorage($data['order_disposables']);
        }

        if ($slug) {
            $attrs['slug'] = $slug;
        }

        if (array_key_exists('instagram', $data)) {
            $instagram = trim((string) ($data['instagram'] ?? ''));
            $attrs['instagram'] = $instagram !== '' ? ltrim($instagram, '@') : null;
        }

        return $attrs;
    }

    protected function normalizeOpeningHours(?array $hours): ?array
    {
        if (! $hours) {
            return null;
        }

        $normalized = [];
        foreach ($hours as $day => $range) {
            if (! is_array($range) || empty($range[0]) || empty($range[1])) {
                continue;
            }
            $normalized[$day] = [$range[0], $range[1]];
        }

        return $normalized ?: null;
    }
}
