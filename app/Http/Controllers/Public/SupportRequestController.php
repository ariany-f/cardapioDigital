<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Services\ActivityLogService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SupportRequestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Public/Support', [
            'types' => [
                ['value' => 'help', 'label' => 'Ajuda / dúvida'],
                ['value' => 'complaint', 'label' => 'Reclamação'],
                ['value' => 'return', 'label' => 'Devolução'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::get();
        $customer = Auth::guard('customer')->user();

        $data = $request->validate([
            'type' => ['required', 'in:help,complaint,return'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'guest_name' => [$customer ? 'nullable' : 'required', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
            'guest_email' => ['nullable', 'email', 'max:255'],
            'order_number' => ['nullable', 'string', 'max:50'],
        ]);

        $orderId = null;
        if (! empty($data['order_number'])) {
            $order = \App\Models\Order::query()
                ->where('order_number', $data['order_number'])
                ->first();
            $orderId = $order?->id;
        }

        $request = SupportRequest::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer?->id,
            'order_id' => $orderId,
            'type' => $data['type'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'open',
            'guest_name' => $customer?->name ?? $data['guest_name'],
            'guest_phone' => $customer?->phone ?? $data['guest_phone'] ?? null,
            'guest_email' => $customer?->email ?? $data['guest_email'] ?? null,
        ]);

        app(ActivityLogService::class)->log(
            $request,
            'support.created',
            'Solicitação aberta pelo cliente',
            ['type' => $data['type'], 'subject' => $data['subject']],
            $customer ? 'customer' : 'guest',
        );

        return redirect()
            ->route('tenant.home', ['tenant' => $tenant->slug])
            ->with(
                'success',
                'Sua mensagem foi encaminhada ao restaurante. O App Cardápio não responde por pedidos — aguarde o contato do estabelecimento.',
            );
    }
}
