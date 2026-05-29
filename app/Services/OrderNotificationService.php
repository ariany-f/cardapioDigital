<?php

namespace App\Services;

use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

/**
 * Avisos automáticos ao cliente quando o restaurante altera o status no painel.
 * Não substitui suporte da plataforma — apenas comunica o que foi registrado pelo estabelecimento.
 */
class OrderNotificationService
{
    protected array $labels = [
        'pending_approval' => 'Aguardando confirmação',
        'confirmed' => 'Confirmado',
        'preparing' => 'Em preparo',
        'ready' => 'Pronto',
        'out_for_delivery' => 'Saiu para entrega',
        'delivered' => 'Entregue',
        'cancelled' => 'Cancelado',
        'rejected' => 'Recusado',
    ];

    public function notifyStatusChange(Order $order, string $status): void
    {
        $email = $order->guest_email;

        if (! $email) {
            return;
        }

        $label = $this->labels[$status] ?? $status;

        $order->loadMissing(['branch', 'tenant']);

        Mail::to($email)->send(new OrderStatusUpdatedMail(
            $order->fresh(['branch', 'tenant']),
            $label,
            $order->tenant?->name,
        ));
    }
}
