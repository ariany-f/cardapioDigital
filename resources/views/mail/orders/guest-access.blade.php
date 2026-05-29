<x-mail::message>
# Pedido {{ $order->order_number }}

Obrigado pelo pedido em **{{ $tenant->name }}**.

**Código de acesso:** {{ $order->guest_access_code }}

Use este código com o telefone ou e-mail informados no pedido para acompanhar o status.

<x-mail::button :url="$trackUrl">
Ver pedido agora
</x-mail::button>

Também pode acessar pela página [Acompanhar pedido]({{ $lookupUrl }}) informando o número do pedido, o código e seu telefone ou e-mail.

Quando **{{ $tenant->name }}** atualizar o status no painel, você pode receber avisos automáticos por e-mail. Isso é apenas comunicação informativa da plataforma — dúvidas e suporte sobre o pedido são com o restaurante.

@include('mail.partials.platform-disclaimer')

Obrigado,<br>
{{ $tenant->name }}
</x-mail::message>
