<?php

namespace App\Support;

use App\Models\Delivery;
use App\Models\Motoboy;
use App\Models\Tenant;

class TenantFeatures
{
    public static function motoboysEnabled(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return false;
        }

        $settings = $tenant->settings_json ?? [];

        if (($settings['motoboys_enabled'] ?? true) === false) {
            return false;
        }

        return TenantPlanFeatures::has($tenant, 'motoboys');
    }

    public static function motoboysAllowedByPlan(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return true;
        }

        return TenantPlanFeatures::has($tenant, 'motoboys');
    }

    public static function setMotoboysEnabled(Tenant $tenant, bool $enabled): void
    {
        if (! $enabled && self::hasMotoboyDeliveriesInProgress($tenant)) {
            throw new \InvalidArgumentException(__('tenant.features.motoboys_disable_blocked'));
        }

        self::merge($tenant, ['motoboys_enabled' => $enabled]);
    }

    /**
     * Entrega com entregador atribuído ainda não finalizada (vale para pedidos atuais, não futuros).
     */
    public static function hasMotoboyDeliveriesInProgress(Tenant $tenant): bool
    {
        return self::motoboyDeliveriesInProgressQuery($tenant)->exists();
    }

    public static function motoboyDeliveriesInProgressCount(Tenant $tenant): int
    {
        return self::motoboyDeliveriesInProgressQuery($tenant)->count();
    }

    protected static function motoboyDeliveriesInProgressQuery(Tenant $tenant)
    {
        return Delivery::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('motoboy_id')
            ->where(function ($query) {
                $query->whereIn('status', Motoboy::ACTIVE_DELIVERY_STATUSES)
                    ->orWhere('motoboy_assignment_status', 'pending');
            })
            ->whereHas('order', fn ($query) => $query->whereNotIn('status', ['delivered', 'cancelled', 'rejected']));
    }

    public static function posAllowedByPlan(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return true;
        }

        return TenantPlanFeatures::has($tenant, 'pos');
    }

    public static function posEnabled(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return false;
        }

        $settings = $tenant->settings_json ?? [];

        if (($settings['pos_enabled'] ?? true) === false) {
            return false;
        }

        return TenantPlanFeatures::has($tenant, 'pos');
    }

    public static function setPosEnabled(Tenant $tenant, bool $enabled): void
    {
        self::merge($tenant, ['pos_enabled' => $enabled]);
    }

    public static function kdsAllowedByPlan(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return true;
        }

        return TenantPlanFeatures::has($tenant, 'kds');
    }

    public static function kdsEnabled(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return false;
        }

        $settings = $tenant->settings_json ?? [];

        if (($settings['kds_enabled'] ?? true) === false) {
            return false;
        }

        return TenantPlanFeatures::has($tenant, 'kds');
    }

    public static function setKdsEnabled(Tenant $tenant, bool $enabled): void
    {
        self::merge($tenant, ['kds_enabled' => $enabled]);
    }

    protected static function merge(Tenant $tenant, array $settings): void
    {
        $tenant->update([
            'settings_json' => array_merge($tenant->settings_json ?? [], $settings),
        ]);
    }
}
