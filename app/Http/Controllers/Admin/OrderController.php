<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ManagesOrderWorkflow;
use App\Http\Controllers\Concerns\RecordsOrderStatus;
use App\Http\Controllers\Concerns\ScopesOrdersToUserBranches;
use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DeliveryStatusHistory;
use App\Models\Order;
use App\Services\ActivityLogService;
use App\Services\DeliveryConfirmationService;
use App\Services\OrderCorrectionService;
use App\Services\OrderPaymentService;
use App\Support\TenantContext;
use App\Support\TenantFeatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    use ManagesOrderWorkflow;
    use RecordsOrderStatus;
    use ScopesOrdersToUserBranches;

    public function index(Request $request): Response
    {
        $orders = $this->ordersQuery()
            ->with('branch:id,name')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only('status'),
        ]);
    }

    public function show(string $tenant, Order $order, ActivityLogService $logger): Response
    {
        $this->assertOrderBranchAccess($order);

        if ($order->type === 'delivery' && $order->status === 'out_for_delivery') {
            app(DeliveryConfirmationService::class)->ensureCode($order);
            $order->refresh();
        }

        $order->load([
            'items',
            'branch',
            'customer',
            'delivery.motoboy',
            'approvedByUser:id,name',
            'cancelledByUser:id,name',
            'rejectedByUser:id,name',
            'statusHistories' => fn ($q) => $q->with('changedByUser:id,name')->orderBy('created_at'),
            'activityLogs' => fn ($q) => $q->with(['actorUser:id,name', 'actorCustomer:id,name']),
            'payments',
        ]);

        $tenant = TenantContext::get();
        $motoboysEnabled = TenantFeatures::motoboysEnabled($tenant);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $this->orderShowPayload($order, $logger),
            'motoboys_enabled' => $motoboysEnabled,
            'motoboys' => $motoboysEnabled
                ? \App\Models\Motoboy::query()
                    ->where('is_active', true)
                    ->with('branches:id,name')
                    ->tap(fn ($q) => \App\Support\MotoboyBranchAccess::scopeForBranch($q, (int) $order->branch_id))
                    ->withCount([
                        'deliveries as active_deliveries_count' => fn ($q) => $q->whereIn(
                            'status',
                            \App\Models\Motoboy::ACTIVE_DELIVERY_STATUSES
                        ),
                    ])
                    ->orderBy('name')
                    ->get()
                : [],
        ]);
    }

    public function accept(string $tenant, Order $order): RedirectResponse
    {
        $this->assertOrderBranchAccess($order);
        $this->assertCanAccept($order);
        $this->recordOrderStatus($order, 'confirmed', 'admin', 'Pedido aprovado pelo restaurante');

        return back()->with('success', 'Pedido aprovado.');
    }

    public function reject(Request $request, string $tenant, Order $order): RedirectResponse
    {
        $this->assertOrderBranchAccess($order);
        $this->assertCanReject($order);

        $data = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = $data['cancel_reason'] ?? null;
        $order->update(['cancel_reason' => $reason]);
        $this->recordOrderStatus($order, 'rejected', 'admin', $reason ?? 'Pedido recusado pelo restaurante');

        return back()->with('success', 'Pedido recusado.');
    }

    public function cancel(Request $request, string $tenant, Order $order): RedirectResponse
    {
        $this->assertOrderBranchAccess($order);
        $this->assertCanCancel($order);

        $data = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $reason = $data['cancel_reason'] ?? null;
        $order->update(['cancel_reason' => $reason]);
        $this->recordOrderStatus($order, 'cancelled', 'admin', $reason ?? 'Pedido cancelado pelo restaurante');

        return back()->with('success', 'Pedido cancelado.');
    }

    public function updateStatus(Request $request, string $tenant, Order $order): RedirectResponse
    {
        $this->assertOrderBranchAccess($order);
        $this->assertCanUpdateStatus($order);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:confirmed,preparing,ready,out_for_delivery,delivered,cancelled,rejected'],
        ]);

        if ($data['status'] === 'confirmed' && $order->status === 'pending_approval') {
            $this->assertCanAccept($order);
        }

        if (in_array($data['status'], ['cancelled', 'rejected'], true)) {
            if ($data['status'] === 'rejected') {
                $this->assertCanReject($order);
            } else {
                $this->assertCanCancel($order);
            }
        }

        if ($data['status'] === 'delivered' && $order->type === 'delivery') {
            throw ValidationException::withMessages([
                'status' => ['Use o código de entrega informado pelo cliente para confirmar a entrega.'],
            ]);
        }

        $this->recordOrderStatus($order, $data['status']);

        return back()->with('success', 'Status atualizado.');
    }

    public function markPaid(string $tenant, Order $order, OrderPaymentService $payments): RedirectResponse
    {
        $this->assertOrderBranchAccess($order);
        $payments->markPaid($order, auth()->guard('web')->id());

        return back()->with('success', 'Pagamento registrado.');
    }

    public function revertPayment(Request $request, string $tenant, Order $order, OrderCorrectionService $corrections): RedirectResponse
    {
        $this->assertOrderBranchAccess($order);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $corrections->revertPayment($order, auth()->guard('web')->id(), $data['reason'] ?? null);

        return back()->with('success', 'Confirmação de pagamento desfeita.');
    }

    public function correctStatus(Request $request, string $tenant, Order $order, OrderCorrectionService $corrections): RedirectResponse
    {
        $this->assertOrderBranchAccess($order);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending_approval,confirmed,preparing,ready,out_for_delivery,delivered,cancelled,rejected'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $corrections->correctStatus($order, $data['status'], $data['reason'], auth()->guard('web')->id());

        return back()->with('success', 'Status do pedido corrigido.');
    }

    protected function orderShowPayload(Order $order, ActivityLogService $logger): array
    {
        $data = $order->toArray();
        $data['audit'] = [
            'approved_at' => $order->approved_at?->format('d/m/Y H:i:s'),
            'approved_by_name' => $order->approvedByUser?->name,
            'cancelled_at' => $order->cancelled_at?->format('d/m/Y H:i:s'),
            'cancelled_by_name' => $order->cancelledByUser?->name,
            'rejected_at' => $order->rejected_at?->format('d/m/Y H:i:s'),
            'rejected_by_name' => $order->rejectedByUser?->name,
            'delivery_confirmed_at' => $order->delivery_confirmed_at?->format('d/m/Y H:i:s'),
        ];
        $data['status_histories'] = $order->statusHistories->map(fn ($h) => [
            ...$h->toArray(),
            'changed_by_name' => $h->changedByUser?->name,
            'created_at_formatted' => $h->created_at?->format('d/m/Y H:i:s'),
        ]);
        $data['activity_logs'] = $order->activityLogs->map(fn ($log) => $logger->formatForUi($log));
        $data['payments'] = $order->payments->map(fn ($p) => [
            'id' => $p->id,
            'gateway' => $p->gateway,
            'amount' => $p->amount,
            'status' => $p->status,
            'copy_paste' => $p->copy_paste,
            'paid_at' => $p->paid_at?->format('d/m/Y H:i'),
        ]);

        return $data;
    }

    public function confirmDelivery(
        Request $request,
        string $tenant,
        Order $order,
        DeliveryConfirmationService $confirmation,
    ): RedirectResponse {
        $this->assertOrderBranchAccess($order);

        if ($order->type !== 'delivery') {
            abort(422, 'Pedido não é de entrega.');
        }

        $this->assertCanUpdateStatus($order);

        $data = $request->validate([
            'confirmation_code' => ['required', 'string', 'min:4', 'max:8'],
        ]);

        $confirmation->confirm($order, $data['confirmation_code']);

        $delivery = Delivery::query()->firstOrCreate(
            ['order_id' => $order->id],
            ['tenant_id' => $order->tenant_id, 'status' => 'pending'],
        );

        if ($delivery->status !== 'delivered') {
            $delivery->update(['status' => 'delivered']);
            DeliveryStatusHistory::create([
                'delivery_id' => $delivery->id,
                'status' => 'delivered',
                'changed_by' => auth()->id(),
                'origin' => 'admin',
            ]);
        }

        $this->recordOrderStatus($order->fresh(), 'delivered', 'admin', 'Entrega confirmada com código');

        return back()->with('success', 'Entrega confirmada com sucesso.');
    }
}
