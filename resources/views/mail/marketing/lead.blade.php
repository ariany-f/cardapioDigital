<x-mail::message>
# Nova solicitação de acesso

**Restaurante:** {{ $lead['restaurant_name'] }}

**Contato:** {{ $lead['contact_name'] }}

**E-mail:** {{ $lead['email'] }}

**Telefone:** {{ $lead['phone'] ?? '—' }}

**Cidade:** {{ $lead['city'] ?? '—' }}

@if(!empty($lead['message']))
**Mensagem:**

{{ $lead['message'] }}
@endif

---

Enviado em {{ $lead['submitted_at'] }} via landing App Cardápio.
</x-mail::message>
