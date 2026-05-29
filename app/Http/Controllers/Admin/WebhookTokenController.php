<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TenantWebhookToken;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebhookTokenController extends Controller
{
    public function index(): Response
    {
        $tenant = TenantContext::get();

        return Inertia::render('Admin/Webhooks/Index', [
            'tokens' => TenantWebhookToken::query()
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('created_at')
                ->get(['id', 'name', 'type', 'is_active', 'created_at', 'token']),
            'webhookUrl' => url('/api/webhooks/delivery'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::get();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        TenantWebhookToken::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'token' => Str::random(48),
            'type' => 'delivery',
            'is_active' => true,
        ]);

        return back()->with('success', 'Token criado. Copie o valor agora — ele não será exibido novamente na lista completa.');
    }

    public function update(Request $request, string $tenant, TenantWebhookToken $webhookToken): RedirectResponse
    {
        $this->assertTenantToken($webhookToken);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $webhookToken->update($data);

        return back()->with('success', 'Token atualizado.');
    }

    public function rotate(string $tenant, TenantWebhookToken $webhookToken): RedirectResponse
    {
        $this->assertTenantToken($webhookToken);

        $webhookToken->update(['token' => Str::random(48)]);

        return back()->with('success', 'Token rotacionado. Atualize a integração com o novo valor.');
    }

    public function destroy(string $tenant, TenantWebhookToken $webhookToken): RedirectResponse
    {
        $this->assertTenantToken($webhookToken);

        $webhookToken->delete();

        return back()->with('success', 'Token removido.');
    }

    protected function assertTenantToken(TenantWebhookToken $token): void
    {
        if ($token->tenant_id !== TenantContext::id()) {
            abort(403);
        }
    }
}
