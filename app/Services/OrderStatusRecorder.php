<?php

namespace App\Services;

use App\Http\Controllers\Concerns\RecordsOrderStatus;
use App\Models\Order;

class OrderStatusRecorder
{
    use RecordsOrderStatus;

    public function record(
        Order $order,
        string $status,
        string $origin = 'admin',
        ?string $notes = null,
    ): void {
        $this->recordOrderStatus($order, $status, $origin, $notes);
    }
}
