<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Reports/Index');
    }

    public function orders(Request $request): StreamedResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $filename = 'pedidos-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Número', 'Data', 'Filial', 'Cliente', 'Telefone', 'Tipo', 'Status',
                'Subtotal', 'Entrega', 'Desconto', 'Total', 'Pagamento',
            ], ';');

            Order::query()
                ->with('branch:id,name')
                ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
                ->orderBy('created_at')
                ->chunk(200, function ($orders) use ($handle) {
                    foreach ($orders as $order) {
                        fputcsv($handle, [
                            $order->order_number,
                            $order->created_at?->format('d/m/Y H:i'),
                            $order->branch?->name,
                            $order->guest_name,
                            $order->guest_phone,
                            $order->type,
                            $order->status,
                            $order->subtotal,
                            $order->delivery_fee,
                            $order->discount_amount,
                            $order->total,
                            $order->payment_status,
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
