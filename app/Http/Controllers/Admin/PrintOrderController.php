<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPrintLog;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintOrderController extends Controller
{
    public function show(Request $request, string $tenant, Order $order): View
    {
        $order->load(['items', 'branch', 'customer']);

        $format = $request->query('format', $order->branch->print_format ?? 'thermal_80mm');
        $type = $request->query('type', 'kitchen');

        OrderPrintLog::query()->create([
            'order_id' => $order->id,
            'format' => $format,
            'ticket_type' => $type,
            'printed_by' => $request->user()?->id,
        ]);

        $view = match ($format) {
            'thermal_58mm' => 'print.thermal_58',
            'a4_summary', 'a4_detail' => 'print.a4',
            default => 'print.thermal_80',
        };

        return view($view, [
            'order' => $order,
            'tenant' => TenantContext::get(),
            'ticketType' => $type,
            'format' => $format,
        ]);
    }
}
