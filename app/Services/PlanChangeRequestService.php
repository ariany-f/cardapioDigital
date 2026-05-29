<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanChangeRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanChangeRequestService
{
    public function __construct(
        protected TenantPlanService $tenantPlans,
    ) {}

    public function create(Tenant $tenant, User $requestedBy, int $requestedPlanId, ?string $message = null): PlanChangeRequest
    {
        $plan = Plan::query()
            ->where('is_active', true)
            ->find($requestedPlanId);

        if (! $plan) {
            throw ValidationException::withMessages([
                'requested_plan_id' => __('tenant.plan_change.invalid_plan'),
            ]);
        }

        $tenant->loadMissing('activeSubscription');
        $currentPlanId = $tenant->activeSubscription?->plan_id;

        if ((int) $currentPlanId === (int) $plan->id) {
            throw ValidationException::withMessages([
                'requested_plan_id' => __('tenant.plan_change.same_plan'),
            ]);
        }

        if (PlanChangeRequest::query()->where('tenant_id', $tenant->id)->pending()->exists()) {
            throw ValidationException::withMessages([
                'requested_plan_id' => __('tenant.plan_change.pending_exists'),
            ]);
        }

        return PlanChangeRequest::create([
            'tenant_id' => $tenant->id,
            'current_plan_id' => $currentPlanId,
            'requested_plan_id' => $plan->id,
            'requested_by' => $requestedBy->id,
            'message' => $message,
            'status' => PlanChangeRequest::STATUS_PENDING,
        ]);
    }

    public function approve(PlanChangeRequest $request, User $reviewer, ?string $adminNotes = null): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => __('tenant.plan_change.already_reviewed'),
            ]);
        }

        $request->load(['tenant', 'requestedPlan']);

        DB::transaction(function () use ($request, $reviewer, $adminNotes): void {
            $this->tenantPlans->applyPlanToTenant($request->tenant, $request->requestedPlan);

            $request->update([
                'status' => PlanChangeRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'admin_notes' => $adminNotes,
            ]);
        });
    }

    public function reject(PlanChangeRequest $request, User $reviewer, ?string $adminNotes = null): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => __('tenant.plan_change.already_reviewed'),
            ]);
        }

        $request->update([
            'status' => PlanChangeRequest::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'admin_notes' => $adminNotes,
        ]);
    }

    /** @return list<Plan> */
    public function availablePlansForTenant(Tenant $tenant): array
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('price_monthly')
            ->get(['id', 'name', 'slug', 'price_monthly', 'features_json']);

        $currentPlanId = $tenant->activeSubscription?->plan_id;

        if ($currentPlanId && ! $plans->contains('id', $currentPlanId)) {
            $current = Plan::query()->find($currentPlanId, ['id', 'name', 'slug', 'price_monthly', 'features_json']);

            if ($current) {
                $plans->push($current);
                $plans = $plans->sortBy('price_monthly')->values();
            }
        }

        return $plans->all();
    }
}
