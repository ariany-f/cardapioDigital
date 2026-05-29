<?php

namespace App\Support;

use App\Models\Tenant;

class TenantPaymentSettings
{
    public static function from(Tenant $tenant): array
    {
        $json = $tenant->settings_json ?? [];

        return [
            'pix_enabled' => (bool) ($json['pix_enabled'] ?? true),
            'pix_key_type' => $json['pix_key_type'] ?? 'phone',
            'pix_key' => $json['pix_key'] ?? '',
            'pix_beneficiary' => $json['pix_beneficiary'] ?? $tenant->name,
            'card_online_enabled' => (bool) ($json['card_online_enabled'] ?? false),
            'card_online_instructions' => $json['card_online_instructions'] ?? '',
        ];
    }

    public static function merge(Tenant $tenant, array $settings): void
    {
        $tenant->update([
            'settings_json' => array_merge($tenant->settings_json ?? [], $settings),
        ]);
    }

    public static function copyPastePayload(Tenant $tenant): ?array
    {
        $s = self::from($tenant);
        if (! $s['pix_enabled'] || blank($s['pix_key'])) {
            return null;
        }

        return [
            'key_type' => $s['pix_key_type'],
            'key' => $s['pix_key'],
            'beneficiary' => $s['pix_beneficiary'],
            'copy_paste' => $s['pix_key'],
        ];
    }
}
