<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\MotoboyReport;
use App\Models\Order;
use App\Services\ActivityLogService;
use App\Support\GuestOrderAccess;
use App\Support\TenantContext;
use App\Support\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MotoboyReportController extends Controller
{
    public function store(Request $request, string $tenant, string $order_number, ActivityLogService $logger): RedirectResponse
    {
        if (! TenantFeatures::motoboysEnabled(TenantContext::get())) {
            abort(403);
        }

        $customer = Auth::guard('customer')->user();

        $order = Order::withoutGlobalScopes()
            ->where('order_number', $order_number)
            ->with('delivery')
            ->firstOrFail();

        if (! $order->delivery?->motoboy_id) {
            return back()->withErrors(['message' => 'Este pedido não tem entregador atribuído.']);
        }

        if ($customer && $order->customer_id !== $customer->id) {
            abort(403);
        }

        if (! $customer) {
            abort_unless(GuestOrderAccess::hasAccess($request, $order), 403);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'guest_name' => [$customer ? 'nullable' : 'required', 'string', 'max:255'],
        ]);

        MotoboyReport::create([
            'tenant_id' => $order->tenant_id,
            'motoboy_id' => $order->delivery->motoboy_id,
            'customer_id' => $customer?->id,
            'order_id' => $order->id,
            'message' => $data['message'],
            'status' => 'open',
        ]);

        $logger->log(
            $order,
            'motoboy.reported',
            'Cliente denunciou o entregador',
            ['motoboy_id' => $order->delivery->motoboy_id],
            'customer',
        );

        return back()->with('success', 'Sua denúncia foi enviada ao restaurante. Entraremos em contato se necessário.');
    }
}
