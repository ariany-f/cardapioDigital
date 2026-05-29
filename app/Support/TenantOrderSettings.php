<?php

namespace App\Support;

use App\Models\Tenant;

class TenantOrderSettings
{
    public static function from(Tenant $tenant): array
    {
        $json = $tenant->settings_json ?? [];

        return [
            'guest_checkout_enabled' => ($json['guest_checkout_enabled'] ?? true) !== false,
        ];
    }

    public static function guestCheckoutEnabled(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return true;
        }

        return self::from($tenant)['guest_checkout_enabled'];
    }

    public static function merge(Tenant $tenant, array $settings): void
    {
        $tenant->update([
            'settings_json' => array_merge($tenant->settings_json ?? [], $settings),
        ]);
    }
}
