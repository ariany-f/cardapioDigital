<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\TenantFeatures;
use App\Support\TenantPlanFeatures;
use Illuminate\Validation\ValidationException;

class TenantPlanService
{
    public function syncSubscriptionPlan(Tenant $tenant, int $planId): void
    {
        $subscription = $tenant->activeSubscription;

        if ($subscription) {
            if ((int) $subscription->plan_id !== $planId) {
                $subscription->update(['plan_id' => $planId]);
            }

            return;
        }

        TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $planId,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
            'payment_status' => 'pending',
            'status' => 'active',
        ]);
    }

    public function applyPlanToTenant(Tenant $tenant, Plan $plan): void
    {
        $this->assertCanApplyPlan($tenant, $plan);

        $this->syncSubscriptionPlan($tenant, $plan->id);
        $this->alignTenantModulesWithPlan($tenant, $plan);
    }

    public function assertCanApplyPlan(Tenant $tenant, Plan $plan): void
    {
        if (
            ! TenantPlanFeatures::planAllows($plan, 'motoboys')
            && TenantFeatures::motoboysEnabled($tenant)
            && TenantFeatures::hasMotoboyDeliveriesInProgress($tenant)
        ) {
            throw ValidationException::withMessages([
                'plan' => __('tenant.plan_change.motoboys_in_progress'),
            ]);
        }
    }

    public function alignTenantModulesWithPlan(Tenant $tenant, Plan $plan): void
    {
        if (! TenantPlanFeatures::planAllows($plan, 'motoboys')) {
            TenantFeatures::setMotoboysEnabled($tenant, false);
        }

        if (! TenantPlanFeatures::planAllows($plan, 'pos')) {
            TenantFeatures::setPosEnabled($tenant, false);
        }

        if (! TenantPlanFeatures::planAllows($plan, 'kds')) {
            TenantFeatures::setKdsEnabled($tenant, false);
        }
    }
}
