<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlanChangeRequest;
use App\Services\PlanChangeRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanChangeRequestController extends Controller
{
    public function __construct(
        protected PlanChangeRequestService $planChangeRequests,
    ) {}

    public function index(Request $request): Response
    {
        $requests = PlanChangeRequest::query()
            ->with([
                'tenant:id,name,slug',
                'currentPlan:id,name,price_monthly',
                'requestedPlan:id,name,price_monthly',
                'requestedByUser:id,name,email',
                'reviewedByUser:id,name',
            ])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->q, function ($q, $term) {
                $q->whereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Platform/PlanChangeRequests/Index', [
            'requests' => $requests,
            'filters' => $request->only(['q', 'status']),
            'statusLabels' => PlanChangeRequest::statusLabels(),
            'pendingCount' => PlanChangeRequest::pending()->count(),
        ]);
    }

    public function approve(Request $request, PlanChangeRequest $planChangeRequest): RedirectResponse
    {
        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->planChangeRequests->approve(
            $planChangeRequest,
            $request->user(),
            $data['admin_notes'] ?? null,
        );

        return back()->with('success', 'Migração de plano aprovada.');
    }

    public function reject(Request $request, PlanChangeRequest $planChangeRequest): RedirectResponse
    {
        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->planChangeRequests->reject(
            $planChangeRequest,
            $request->user(),
            $data['admin_notes'] ?? null,
        );

        return back()->with('success', 'Solicitação recusada.');
    }
}
