<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Order;
use Illuminate\Validation\ValidationException;

trait ManagesOrderWorkflow
{
    protected function assertCanAccept(Order $order): void
    {
        if ($order->status !== 'pending_approval') {
            throw ValidationException::withMessages([
                'order' => ['Este pedido já foi processado e não pode ser aprovado novamente.'],
            ]);
        }
    }

    protected function assertCanReject(Order $order): void
    {
        if ($order->status !== 'pending_approval') {
            throw ValidationException::withMessages([
                'order' => ['Só é possível recusar pedidos aguardando confirmação.'],
            ]);
        }
    }

    protected function assertCanCancel(Order $order): void
    {
        if (in_array($order->status, ['cancelled', 'rejected', 'delivered'], true)) {
            throw ValidationException::withMessages([
                'order' => ['Este pedido não pode ser cancelado.'],
            ]);
        }
    }

    protected function assertCanUpdateStatus(Order $order): void
    {
        if (in_array($order->status, ['cancelled', 'rejected', 'delivered'], true)) {
            throw ValidationException::withMessages([
                'order' => ['O status deste pedido não pode mais ser alterado.'],
            ]);
        }
    }
}
