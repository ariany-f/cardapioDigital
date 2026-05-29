<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryStatusHistory;
use App\Models\Motoboy;
use App\Models\Order;
use App\Support\MotoboyBranchAccess;
use App\Support\TenantDeliverySettings;
use Illuminate\Support\Facades\DB;

class DeliveryStatusService
{
    public function __construct(
        protected DeliveryConfirmationService $confirmation,
        protected ActivityLogService $logger,
        protected OrderStatusRecorder $orderStatus,
    ) {}

    public function assignMotoboy(Order $order, ?int $motoboyId, ?int $adminUserId = null): Delivery
    {
        $delivery = $this->ensureDelivery($order);
        $tenant = $order->tenant ?? $order->tenant()->first();
        $settings = TenantDeliverySettings::from($tenant);

        if (! $motoboyId) {
            $delivery->update([
                'motoboy_id' => null,
                'motoboy_assignment_status' => null,
                'motoboy_assigned_at' => null,
                'motoboy_responded_at' => null,
                'motoboy_reject_reason' => null,
            ]);

            return $delivery->fresh();
        }

        $motoboy = Motoboy::query()->findOrFail($motoboyId);
        MotoboyBranchAccess::assertCanServeBranch($motoboy, (int) $order->branch_id);

        if ($motoboy->usesApp()) {
            $autoAccept = $settings['motoboy_auto_accept_assignments'];
            $assignmentStatus = $autoAccept ? 'accepted' : 'pending';
            $logMessage = $autoAccept
                ? 'Entregador (app) atribuído — aceite automático'
                : 'Entregador (painel web) atribuído — aguardando aceite no painel';
        } else {
            $autoAccept = true;
            $assignmentStatus = 'accepted';
            $logMessage = 'Entregador externo atribuído (comanda impressa — sem app)';
        }

        $delivery->update([
            'motoboy_id' => $motoboyId,
            'motoboy_assignment_status' => $assignmentStatus,
            'motoboy_assigned_at' => now(),
            'motoboy_responded_at' => $autoAccept ? now() : null,
            'motoboy_reject_reason' => null,
            'status' => $autoAccept ? 'assigned' : ($delivery->status === 'pending' ? 'assigned' : $delivery->status),
        ]);

        $this->recordHistory($delivery, $delivery->status, $adminUserId, 'admin');

        $this->logger->log(
            $order,
            'order.motoboy_assigned',
            $logMessage,
            ['motoboy_id' => $motoboyId, 'assignment_status' => $assignmentStatus, 'uses_app' => $motoboy->usesApp()],
            'admin',
        );

        return $delivery->fresh();
    }

    public function motoboyRespond(Delivery $delivery, Motoboy $motoboy, bool $accept, ?string $rejectReason = null): void
    {
        if (! $motoboy->usesApp()) {
            abort(403, 'Este entregador não utiliza o painel web.');
        }

        $delivery->loadMissing('order');
        MotoboyBranchAccess::assertCanServeBranch($motoboy, (int) $delivery->order->branch_id);

        if ($delivery->motoboy_id !== $motoboy->id) {
            abort(403);
        }

        if ($delivery->motoboy_assignment_status !== 'pending') {
            abort(422, 'Esta entrega não está aguardando sua resposta.');
        }

        if ($accept) {
            $delivery->update([
                'motoboy_assignment_status' => 'accepted',
                'motoboy_responded_at' => now(),
                'status' => 'assigned',
            ]);
            $this->recordHistory($delivery, 'assigned', null, 'motoboy', $motoboy->id);
            $this->logger->log(
                $delivery->order,
                'order.motoboy_accepted',
                'Motoboy aceitou a entrega',
                ['motoboy_id' => $motoboy->id],
                'motoboy',
            );
        } else {
            $delivery->update([
                'motoboy_assignment_status' => 'rejected',
                'motoboy_responded_at' => now(),
                'motoboy_reject_reason' => $rejectReason,
                'motoboy_id' => null,
                'status' => 'pending',
            ]);
            $this->recordHistory($delivery, 'pending', null, 'motoboy', $motoboy->id);
            $this->logger->log(
                $delivery->order,
                'order.motoboy_rejected',
                'Motoboy recusou a entrega',
                ['motoboy_id' => $motoboy->id, 'reason' => $rejectReason],
                'motoboy',
            );
        }
    }

    public function updateStatus(
        Order $order,
        string $deliveryStatus,
        ?int $motoboyId = null,
        ?string $confirmationCode = null,
        ?int $userId = null,
        string $origin = 'admin',
        ?int $motoboyActorId = null,
    ): void {
        DB::transaction(function () use ($order, $deliveryStatus, $motoboyId, $confirmationCode, $userId, $origin, $motoboyActorId) {
            $delivery = $this->ensureDelivery($order);

            if ($motoboyId !== null && (int) $motoboyId !== (int) $delivery->motoboy_id) {
                $this->assignMotoboy($order, $motoboyId ?: null, $userId);
                $delivery = $delivery->fresh();
            }

            if ($delivery->motoboy_id && $delivery->motoboy_assignment_status === 'pending') {
                $assigned = Motoboy::query()->find($delivery->motoboy_id);
                if ($assigned?->usesApp()) {
                    abort(422, 'O entregador ainda não aceitou esta entrega no painel web.');
                }
            }

            if ($deliveryStatus === 'delivered' && $order->status !== 'delivered') {
                $this->confirmation->confirm($order, $confirmationCode ?? '');
            }

            if (in_array($deliveryStatus, ['picked_up', 'on_route'], true)) {
                $this->confirmation->ensureCode($order->fresh());
            }

            $delivery->update(['status' => $deliveryStatus]);
            $this->recordHistory($delivery, $deliveryStatus, $userId, $origin, $motoboyActorId);

            $orderStatusMap = [
                'picked_up' => 'out_for_delivery',
                'on_route' => 'out_for_delivery',
                'delivered' => 'delivered',
            ];

            if (isset($orderStatusMap[$deliveryStatus])) {
                $notes = $deliveryStatus === 'delivered' ? 'Entrega confirmada com código' : null;
                $this->orderStatus->record($order, $orderStatusMap[$deliveryStatus], $origin, $notes);
            } else {
                $this->logger->log(
                    $order,
                    'order.delivery_updated',
                    sprintf('Entrega: status %s', $deliveryStatus),
                    ['delivery_status' => $deliveryStatus, 'motoboy_id' => $delivery->motoboy_id],
                    $origin,
                );
            }
        });
    }

    public function ensureDelivery(Order $order): Delivery
    {
        return Delivery::query()->firstOrCreate(
            ['order_id' => $order->id],
            ['tenant_id' => $order->tenant_id, 'status' => 'pending'],
        );
    }

    /**
     * Libera entregador quando o pedido é cancelado ou recusado.
     */
    public function releaseForTerminalOrder(Order $order, string $orderStatus): void
    {
        $delivery = Delivery::query()->where('order_id', $order->id)->first();

        if (! $delivery) {
            return;
        }

        $wasActive = in_array($delivery->status, Motoboy::ACTIVE_DELIVERY_STATUSES, true)
            || $delivery->motoboy_assignment_status === 'pending';

        if (! $wasActive) {
            return;
        }

        $deliveryStatus = $orderStatus === 'rejected' ? 'failed' : 'cancelled';
        $motoboyId = $delivery->motoboy_id;

        $delivery->update([
            'status' => $deliveryStatus,
            'motoboy_assignment_status' => null,
        ]);

        $this->recordHistory(
            $delivery,
            $deliveryStatus,
            auth()->guard('web')->id(),
            'system',
        );

        if ($motoboyId) {
            $this->syncMotoboyAvailabilityAfterRelease((int) $motoboyId);
        }
    }

    protected function syncMotoboyAvailabilityAfterRelease(int $motoboyId): void
    {
        $motoboy = Motoboy::query()->find($motoboyId);

        if (! $motoboy || $motoboy->operational_status !== 'busy') {
            return;
        }

        $hasInProgress = Delivery::query()
            ->where('motoboy_id', $motoboyId)
            ->inProgressForMotoboy()
            ->exists();

        if (! $hasInProgress) {
            $motoboy->update(['operational_status' => 'available']);
        }
    }

    protected function recordHistory(
        Delivery $delivery,
        string $status,
        ?int $userId,
        string $origin,
        ?int $motoboyId = null,
    ): void {
        DeliveryStatusHistory::create([
            'delivery_id' => $delivery->id,
            'status' => $status,
            'changed_by' => $userId,
            'origin' => $origin,
        ]);
    }
}
