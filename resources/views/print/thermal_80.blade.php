<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Pedido {{ $order->order_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 4mm; }
        body { font-family: monospace; font-size: 12px; width: 72mm; margin: 0 auto; }
        h1 { font-size: 14px; margin: 0 0 8px; text-align: center; }
        .line { border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; }
        .right { text-align: right; }
    </style>
</head>
<body onload="window.print()">
    <h1>{{ $tenant->name }}</h1>
    <p><strong>#{{ $order->order_number }}</strong> — {{ strtoupper($ticketType) }}</p>
    <p>{{ $order->branch->name }}<br>{{ now()->format('d/m/Y H:i') }}</p>
    <div class="line"></div>
    @if($order->guest_name)
        <p>{{ $order->guest_name }} — {{ $order->guest_phone }}</p>
    @endif
    @if($order->type === 'delivery' && $order->delivery_address)
        <p>Entrega: {{ json_encode($order->delivery_address) }}</p>
    @endif
    <table>
        @foreach($order->items as $item)
            <tr>
                <td>{{ $item->quantity }}x {{ $item->name }}</td>
                <td class="right">R$ {{ number_format($item->total_price, 2, ',', '.') }}</td>
            </tr>
            @if($item->notes)
                <tr><td colspan="2"><small>* {{ $item->notes }}</small></td></tr>
            @endif
        @endforeach
    </table>
    <div class="line"></div>
    @if($ticketType !== 'kitchen')
        <p class="right"><strong>Total: R$ {{ number_format($order->total, 2, ',', '.') }}</strong></p>
        <p>Pagamento: na entrega ({{ $order->payment_channel ?? '—' }})</p>
    @endif
    @if($order->notes)
        <p>Obs: {{ $order->notes }}</p>
    @endif
</body>
</html>
