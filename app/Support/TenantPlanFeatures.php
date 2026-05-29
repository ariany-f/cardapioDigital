<?php

namespace App\Support;

use App\Models\Plan;
use App\Models\Tenant;

class TenantPlanFeatures
{
    public static function has(Tenant $tenant, string $feature): bool
    {
        $subscription = $tenant->activeSubscription()->with('plan')->first();

        if (! $subscription?->plan) {
            return true;
        }

        return self::planAllows($subscription->plan, $feature);
    }

    public static function planAllows(?Plan $plan, string $feature): bool
    {
        if (! $plan) {
            return true;
        }

        $features = $plan->features_json ?? [];

        return (bool) ($features[$feature] ?? false);
    }
}
