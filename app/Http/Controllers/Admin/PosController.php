<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Services\ActivityLogService;
use App\Services\BranchCatalogService;
use App\Services\OrderDeliveryEstimateService;
use App\Services\OrderItemValidationService;
use App\Services\OrderPaymentService;
use App\Support\BranchAccess;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(BranchCatalogService $catalog): Response
    {
        return Inertia::render('Admin/Pos', [
            'products' => $catalog->productsForPos(),
            'combos' => $catalog->combosForPos(),
            'branches' => BranchAccess::branchesForUser(auth()->user())
                ->where('is_active', true)
                ->map(fn ($b) => $b->only(['id', 'name']))
                ->values(),
        ]);
    }

    public function store(
        Request $request,
        OrderItemValidationService $itemValidation,
        ActivityLogService $logger,
        OrderPaymentService $payments,
        OrderDeliveryEstimateService $deliveryEstimate,
    ): RedirectResponse {
        $tenant = TenantContext::get();

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['required', 'in:on_delivery,pix,cash,card,debit'],
            'mark_paid' => ['boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.combo_id' => ['nullable', 'integer'],
            'items.*.name' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.variations' => ['nullable', 'array'],
            'items.*.variations.*.option_id' => ['required', 'integer'],
            'items.*.variations.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $branch = Branch::query()->findOrFail($data['branch_id']);
        BranchAccess::assertCanAccessBranch($request->user(), (int) $branch->id);
        $itemValidation->validate($data['items'], $branch);

        $subtotal = collect($data['items'])->sum(fn ($item) => $item['unit_price'] * $item['quantity']);

        $orderNumber = strtoupper($tenant->slug).'-'.str_pad(
            (string) (Order::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count() + 1),
            4,
            '0',
            STR_PAD_LEFT
        );

        $prepMinutes = $deliveryEstimate->estimatePrepMinutes($branch, $data['items']);

        $order = Order::create([
            'branch_id' => $branch->id,
            'order_number' => $orderNumber,
            'type' => 'pickup',
            'status' => 'confirmed',
            'source' => 'pos',
            'subtotal' => $subtotal,
            'delivery_fee' => 0,
            'packaging_fee' => 0,
            'discount_amount' => 0,
            'total' => $subtotal,
            'payment_method' => 'on_delivery',
            'payment_channel' => null,
            'payment_status' => 'pending',
            'guest_name' => $data['guest_name'],
            'guest_phone' => $data['guest_phone'] ?? '-',
            'prep_time_minutes' => $prepMinutes,
            'estimated_ready_at' => now($tenant->timezone ?? config('app.timezone'))->addMinutes($prepMinutes),
        ]);

        foreach ($data['items'] as $item) {
            OrderItem::create([
                'tenant_id' => $tenant->id,
                'order_id' => $order->id,
                'product_id' => $item['product_id'] ?? null,
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['unit_price'] * $item['quantity'],
                'variations_snapshot' => $item['variations'] ?? null,
            ]);
        }

        OrderStatusHistory::create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'status' => 'confirmed',
            'origin' => 'admin',
            'changed_by' => auth()->id(),
            'notes' => 'Pedido registrado no PDV',
        ]);

        $order->update([
            'approved_at' => now(),
            'approved_by_user_id' => auth()->id(),
        ]);

        $payments->registerPosPayment(
            $order,
            $data['payment_method'],
            (bool) ($data['mark_paid'] ?? false),
        );

        $logger->log(
            $order->fresh(),
            'order.created',
            'Pedido registrado no PDV (já confirmado)',
            ['source' => 'pos', 'order_number' => $order->order_number, 'total' => $order->total],
            'pos',
        );

        return redirect()
            ->route('tenant.admin.orders.show', ['tenant' => $tenant->slug, 'order' => $order->id])
            ->with('success', 'Pedido PDV registrado.');
    }
}
