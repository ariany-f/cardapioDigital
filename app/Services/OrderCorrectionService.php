<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Validation\ValidationException;

class OrderCorrectionService
{
    public function __construct(
        protected ActivityLogService $logger,
        protected OrderStatusRecorder $statusRecorder,
    ) {}

    public function revertPayment(Order $order, ?int $userId = null, ?string $reason = null): Order
    {
        if ($order->payment_status === 'refunded') {
            throw ValidationException::withMessages([
                'payment' => ['Pagamento já foi estornado e não pode ser desfeito aqui.'],
            ]);
        }

        if ($order->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'payment' => ['Este pedido não está marcado como pago.'],
            ]);
        }

        $order->update(['payment_status' => 'pending']);

        OrderPayment::query()
            ->where('order_id', $order->id)
            ->where('status', 'paid')
            ->update([
                'status' => 'pending',
                'paid_at' => null,
            ]);

        $this->logger->log(
            $order->fresh(),
            'order.payment_reverted',
            'Confirmação de pagamento desfeita',
            ['reason' => $reason, 'payment_status' => 'pending'],
            'admin',
        );

        return $order->fresh();
    }

    public function correctStatus(Order $order, string $status, string $reason, ?int $userId = null): Order
    {
        $allowed = [
            'pending_approval',
            'confirmed',
            'preparing',
            'ready',
            'out_for_delivery',
            'delivered',
            'cancelled',
            'rejected',
        ];

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ['Status inválido.'],
            ]);
        }

        if ($status === $order->status) {
            throw ValidationException::withMessages([
                'status' => ['Selecione um status diferente do atual.'],
            ]);
        }

        if ($status === 'delivered' && $order->type === 'delivery') {
            throw ValidationException::withMessages([
                'status' => ['Para marcar entrega em pedidos delivery, use a confirmação com código ou ajuste para "Saiu para entrega".'],
            ]);
        }

        $previous = $order->status;

        if ($previous === 'delivered') {
            $order->update(['delivery_confirmed_at' => null]);
            $this->syncDeliveryAfterLeavingDelivered($order, $status);
        }

        if (in_array($status, ['out_for_delivery', 'ready', 'preparing', 'confirmed'], true) && in_array($previous, ['cancelled', 'rejected'], true)) {
            $order->update([
                'cancelled_at' => null,
                'cancelled_by_user_id' => null,
                'rejected_at' => null,
                'rejected_by_user_id' => null,
                'cancel_reason' => null,
            ]);
        }

        $note = 'Correção: '.$reason;

        $this->statusRecorder->record($order->fresh(), $status, 'admin', $note);

        $this->logger->log(
            $order->fresh(),
            'order.status_corrected',
            sprintf('Status corrigido (%s → %s)', $previous, $status),
            ['from' => $previous, 'to' => $status, 'reason' => $reason],
            'admin',
        );

        return $order->fresh();
    }

    protected function syncDeliveryAfterLeavingDelivered(Order $order, string $targetStatus): void
    {
        $delivery = Delivery::query()->where('order_id', $order->id)->first();

        if (! $delivery) {
            return;
        }

        $deliveryStatus = match ($targetStatus) {
            'out_for_delivery' => 'on_route',
            'ready' => 'picked_up',
            'preparing', 'confirmed' => 'assigned',
            'cancelled', 'rejected' => 'failed',
            default => 'pending',
        };

        $delivery->update(['status' => $deliveryStatus]);
    }
}
