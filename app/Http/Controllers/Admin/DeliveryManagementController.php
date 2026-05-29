<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ScopesOrdersToUserBranches;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\DeliveryStatusService;
use App\Support\TenantContext;
use App\Support\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliveryManagementController extends Controller
{
    use ScopesOrdersToUserBranches;

    public function update(Request $request, string $tenant, Order $order, DeliveryStatusService $delivery): RedirectResponse
    {
        $this->assertOrderBranchAccess($order);

        if ($order->type !== 'delivery') {
            abort(422, 'Pedido não é de entrega.');
        }

        $data = $request->validate([
            'motoboy_id' => ['nullable', 'exists:motoboys,id'],
            'delivery_status' => ['required', 'string', 'in:pending,assigned,picked_up,on_route,delivered,failed'],
            'confirmation_code' => ['nullable', 'string', 'min:4', 'max:8'],
        ]);

        $motoboyId = TenantFeatures::motoboysEnabled(TenantContext::get())
            ? ($data['motoboy_id'] ?? null)
            : null;

        $delivery->updateStatus(
            $order,
            $data['delivery_status'],
            $motoboyId,
            $data['confirmation_code'] ?? null,
            auth()->id(),
        );

        return back()->with('success', 'Entrega atualizada.');
    }
}
