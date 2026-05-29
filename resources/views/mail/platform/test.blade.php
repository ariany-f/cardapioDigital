<x-mail::message>
# Teste de e-mail

Olá,

Este é um e-mail de teste enviado pelo painel da plataforma por **{{ $sentByName }}**.

Se você recebeu esta mensagem, a configuração SMTP está funcionando.

<x-mail::subcopy>
{{ config('app.name') }}
</x-mail::subcopy>
</x-mail::message>
