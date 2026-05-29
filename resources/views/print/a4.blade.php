<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Pedido {{ $order->order_number }}</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 2rem auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    </style>
</head>
<body onload="window.print()">
    <h1>{{ $tenant->name }} — Pedido {{ $order->order_number }}</h1>
    @include('print.thermal_80')
</body>
</html>
