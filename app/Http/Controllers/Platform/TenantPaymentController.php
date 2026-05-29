<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TenantPaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = TenantPayment::query()
            ->with([
                'tenant:id,name,slug',
                'subscription.plan:id,name',
                'markedBy:id,name',
            ])
            ->when($request->tenant_id, fn ($q, $id) => $q->where('tenant_id', $id))
            ->latest('paid_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only('tenant_id'),
            'tenants' => Tenant::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'filterTenant' => $request->tenant_id
                ? Tenant::query()->find($request->tenant_id, ['id', 'name', 'slug'])
                : null,
        ]);
    }

    public function create(Request $request): Response
    {
        $tenant = $request->tenant_id
            ? Tenant::query()->with('activeSubscription.plan')->find($request->tenant_id)
            : null;

        return Inertia::render('Platform/Payments/Create', [
            'tenants' => Tenant::query()
                ->with('activeSubscription.plan')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'status']),
            'selectedTenant' => $tenant,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->paymentRules(requireTenant: true));

        $tenant = Tenant::query()->with('activeSubscription')->findOrFail($data['tenant_id']);
        $subscription = $tenant->activeSubscription;

        TenantPayment::create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $subscription?->id,
            'amount' => $data['amount'],
            'reference' => $data['reference'],
            'paid_at' => $this->parsePaidAt($data['paid_at'] ?? null),
            'marked_by' => $request->user()->id,
            'notes' => $data['notes'],
        ]);

        if ($subscription) {
            $subscription->update(['payment_status' => 'paid']);
        }

        return redirect()
            ->route('platform.payments.index', ['tenant_id' => $tenant->id])
            ->with('success', 'Pagamento registrado com sucesso.');
    }

    public function edit(TenantPayment $payment): Response
    {
        $payment->load([
            'tenant:id,name,slug',
            'subscription.plan:id,name',
            'markedBy:id,name',
        ]);

        return Inertia::render('Platform/Payments/Edit', [
            'payment' => [
                'id' => $payment->id,
                'tenant_id' => $payment->tenant_id,
                'amount' => $payment->amount,
                'reference' => $payment->reference,
                'notes' => $payment->notes,
                'paid_at' => $payment->paid_at?->format('Y-m-d'),
                'tenant' => $payment->tenant,
                'subscription' => $payment->subscription ? [
                    'plan' => $payment->subscription->plan?->only('id', 'name'),
                ] : null,
                'marked_by' => $payment->markedBy?->only('id', 'name'),
            ],
        ]);
    }

    public function update(Request $request, TenantPayment $payment): RedirectResponse
    {
        $data = $request->validate($this->paymentRules(requireTenant: false));

        $payment->update([
            'amount' => $data['amount'],
            'reference' => $data['reference'],
            'paid_at' => $this->parsePaidAt($data['paid_at'] ?? null),
            'notes' => $data['notes'],
        ]);

        return redirect()
            ->route('platform.payments.index', ['tenant_id' => $payment->tenant_id])
            ->with('success', 'Pagamento atualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function paymentRules(bool $requireTenant): array
    {
        return [
            ...($requireTenant ? [
                'tenant_id' => ['required', 'exists:tenants,id'],
            ] : []),
            'amount' => ['required', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'paid_at' => ['nullable', 'date'],
        ];
    }

    protected function parsePaidAt(?string $date): Carbon
    {
        return $date
            ? Carbon::parse($date)->startOfDay()
            : now();
    }
}
