<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SupportRequest;
use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

class ReturnWorkflowService
{
    public function __construct(
        protected ActivityLogService $logger,
        protected StockService $stock,
    ) {}

    public function processReturn(SupportRequest $request, ?string $notes = null): Order
    {
        if ($request->type !== 'return') {
            throw ValidationException::withMessages(['type' => ['Esta solicitação não é uma devolução.']]);
        }

        $order = $this->resolveOrder($request);

        if (in_array($order->status, ['cancelled', 'rejected'], true)) {
            throw ValidationException::withMessages(['order' => ['O pedido já está cancelado/recusado.']]);
        }

        $previousStatus = $order->status;
        $order->update([
            'status' => 'cancelled',
            'cancel_reason' => $notes ?? 'Devolução processada via suporte #'.$request->id,
            'cancelled_at' => now(),
            'cancelled_by_user_id' => auth()->guard('web')->id(),
        ]);

        $this->stock->restoreForCancelledOrder($order->fresh());
        app(DeliveryStatusService::class)->releaseForTerminalOrder($order->fresh(), 'cancelled');

        if ($order->payment_status === 'paid') {
            $order->update(['payment_status' => 'refunded']);
            $this->logger->log(
                $order,
                'order.payment_refunded',
                'Pagamento marcado como estornado (devolução)',
                ['support_request_id' => $request->id],
                'admin',
            );
        }

        $this->logger->log(
            $order,
            'order.return_processed',
            'Devolução processada a partir do suporte',
            [
                'support_request_id' => $request->id,
                'previous_status' => $previousStatus,
            ],
            'admin',
        );

        $request->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by' => auth()->id(),
            'admin_notes' => trim(($request->admin_notes ?? '')."\n\n[Devolução processada em ".now()->format('d/m/Y H:i').']'),
        ]);

        return $order->fresh();
    }

    protected function resolveOrder(SupportRequest $request): Order
    {
        if ($request->order_id) {
            return Order::withoutGlobalScopes()->findOrFail($request->order_id);
        }

        if ($request->order_number) {
            $tenantId = $request->tenant_id ?? TenantContext::id();
            $order = Order::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('order_number', $request->order_number)
                ->first();

            if ($order) {
                $request->update(['order_id' => $order->id]);

                return $order;
            }
        }

        throw ValidationException::withMessages([
            'order_number' => ['Vincule um pedido válido antes de processar a devolução.'],
        ]);
    }
}
