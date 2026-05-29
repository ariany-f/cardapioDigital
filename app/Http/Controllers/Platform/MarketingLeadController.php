<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\MarketingLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MarketingLeadController extends Controller
{
    public function index(Request $request): Response
    {
        $leads = MarketingLead::query()
            ->when($request->q, function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('restaurant_name', 'like', "%{$term}%")
                        ->orWhere('contact_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('city', 'like', "%{$term}%");
                });
            })
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Platform/MarketingLeads/Index', [
            'leads' => $leads,
            'filters' => $request->only(['q', 'status']),
            'statusLabels' => MarketingLead::statusLabels(),
            'pendingCount' => MarketingLead::pending()->count(),
        ]);
    }

    public function show(MarketingLead $lead): Response
    {
        return Inertia::render('Platform/MarketingLeads/Show', [
            'lead' => $lead,
            'statusLabels' => MarketingLead::statusLabels(),
        ]);
    }

    public function update(Request $request, MarketingLead $lead): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                MarketingLead::STATUS_PENDING,
                MarketingLead::STATUS_CONTACTED,
                MarketingLead::STATUS_ARCHIVED,
            ])],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $contactedAt = $lead->contacted_at;
        if ($data['status'] === MarketingLead::STATUS_CONTACTED && ! $contactedAt) {
            $contactedAt = now();
        }
        if ($data['status'] === MarketingLead::STATUS_PENDING) {
            $contactedAt = null;
        }

        $lead->update([
            'status' => $data['status'],
            'internal_notes' => $data['internal_notes'] ?? null,
            'contacted_at' => $contactedAt,
        ]);

        return redirect()
            ->route('platform.marketing-leads.show', $lead)
            ->with('success', 'Solicitação atualizada.');
    }
}
