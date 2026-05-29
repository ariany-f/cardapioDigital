<?php

namespace App\Support;

use App\Models\Tenant;

class TenantPlanFeatures
{
    public static function has(Tenant $tenant, string $feature): bool
    {
        $subscription = $tenant->activeSubscription()->with('plan')->first();

        if (! $subscription?->plan) {
            return true;
        }

        $features = $subscription->plan->features_json ?? [];

        if (! array_key_exists($feature, $features)) {
            return true;
        }

        return (bool) $features[$feature];
    }
}
