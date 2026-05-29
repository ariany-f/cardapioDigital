<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanChangeRequest;
use App\Services\PlanChangeRequestService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanChangeRequestController extends Controller
{
    public function __construct(
        protected PlanChangeRequestService $planChangeRequests,
    ) {}

    public function index(): Response
    {
        $tenant = TenantContext::get();
        $tenant->loadMissing('activeSubscription.plan');

        $requests = PlanChangeRequest::query()
            ->with(['currentPlan:id,name,price_monthly', 'requestedPlan:id,name,price_monthly', 'requestedByUser:id,name', 'reviewedByUser:id,name'])
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Plan/Index', [
            'currentPlan' => $tenant->activeSubscription?->plan,
            'availablePlans' => $this->planChangeRequests->availablePlansForTenant($tenant),
            'requests' => $requests,
            'hasPendingRequest' => $requests->contains(fn (PlanChangeRequest $r) => $r->isPending()),
            'statusLabels' => PlanChangeRequest::statusLabels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::get();

        $data = $request->validate([
            'requested_plan_id' => ['required', 'integer', 'exists:plans,id'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->planChangeRequests->create(
            $tenant,
            $request->user(),
            (int) $data['requested_plan_id'],
            $data['message'] ?? null,
        );

        return back()->with('success', 'Solicitação de migração de plano enviada. Aguarde a análise da plataforma.');
    }
}
