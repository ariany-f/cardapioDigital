<x-mail::message>
# Atualização do pedido {{ $order->order_number }}

{{ $statusIntro }}

Status registrado pelo restaurante: **{{ $statusLabel }}**

Total: R$ {{ number_format($order->total, 2, ',', '.') }}

@if($order->branch?->name)
Unidade: {{ $order->branch->name }}
@endif

Para dúvidas, atrasos ou problemas com o pedido, entre em contato diretamente com **{{ $restaurantName ?? 'o restaurante' }}** — o App Cardápio não resolve disputas nem reembolsos sobre pedidos.

@include('mail.partials.platform-disclaimer')
</x-mail::message>
