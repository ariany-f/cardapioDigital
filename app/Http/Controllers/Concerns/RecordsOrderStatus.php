<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\ActivityLogService;
use App\Services\DeliveryConfirmationService;
use App\Services\OrderNotificationService;
use App\Services\StockService;

trait RecordsOrderStatus
{
    protected function recordOrderStatus(
        Order $order,
        string $status,
        string $origin = 'admin',
        ?string $notes = null,
    ): void {
        $previous = $order->status;
        $userId = auth()->guard('web')->id();

        $milestones = ['status' => $status];

        if ($status === 'confirmed' && $previous === 'pending_approval') {
            $milestones['approved_at'] = now();
            $milestones['approved_by_user_id'] = $userId;
        }

        if ($status === 'cancelled') {
            $milestones['cancelled_at'] = now();
            $milestones['cancelled_by_user_id'] = $userId;
        }

        if ($status === 'rejected') {
            $milestones['rejected_at'] = now();
            $milestones['rejected_by_user_id'] = $userId;
        }

        $order->update($milestones);

        if (in_array($status, ['cancelled', 'rejected'], true)) {
            app(StockService::class)->restoreForCancelledOrder($order->fresh());
        }

        if ($status === 'out_for_delivery') {
            app(DeliveryConfirmationService::class)->ensureCode($order->fresh());
        }

        OrderStatusHistory::create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'status' => $status,
            'origin' => $origin,
            'changed_by' => $userId,
            'notes' => $notes,
        ]);

        $this->logOrderStatusActivity($order, $previous, $status, $origin, $notes);

        app(OrderNotificationService::class)->notifyStatusChange($order->fresh(), $status);
    }

    protected function logOrderStatusActivity(
        Order $order,
        string $previous,
        string $status,
        string $origin,
        ?string $notes,
    ): void {
        $logger = app(ActivityLogService::class);

        if ($status === 'confirmed' && $previous === 'pending_approval') {
            $logger->log(
                $order,
                'order.approved',
                'Pedido aprovado',
                ['from' => $previous, 'to' => $status, 'notes' => $notes],
                $origin,
            );

            return;
        }

        if ($status === 'cancelled') {
            $logger->log(
                $order,
                'order.cancelled',
                'Pedido cancelado',
                ['from' => $previous, 'to' => $status, 'reason' => $order->cancel_reason, 'notes' => $notes],
                $origin,
            );

            return;
        }

        if ($status === 'rejected') {
            $logger->log(
                $order,
                'order.rejected',
                'Pedido recusado',
                ['from' => $previous, 'to' => $status, 'reason' => $order->cancel_reason, 'notes' => $notes],
                $origin,
            );

            return;
        }

        if ($status === 'delivered' && str_contains((string) $notes, 'código')) {
            $logger->log(
                $order,
                'order.delivery_confirmed',
                'Entrega confirmada com código do cliente',
                ['from' => $previous, 'to' => $status],
                $origin,
            );

            return;
        }

        $logger->log(
            $order,
            'order.status_changed',
            sprintf('Status: %s → %s', $previous, $status),
            ['from' => $previous, 'to' => $status, 'notes' => $notes],
            $origin,
        );
    }
}
