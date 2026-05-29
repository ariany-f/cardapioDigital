<?php

namespace App\Support;

use App\Models\Tenant;

class TenantDeliverySettings
{
    public static function from(Tenant $tenant): array
    {
        $json = $tenant->settings_json ?? [];

        return [
            'motoboy_auto_accept_assignments' => (bool) ($json['motoboy_auto_accept_assignments'] ?? false),
        ];
    }

    public static function merge(Tenant $tenant, array $settings): void
    {
        $tenant->update([
            'settings_json' => array_merge($tenant->settings_json ?? [], $settings),
        ]);
    }
}
