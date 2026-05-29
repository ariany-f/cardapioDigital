<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMotoboyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $motoboy = $request->user('motoboy');
        $tenant = TenantContext::get();

        if (! $motoboy || ! $tenant || $motoboy->tenant_id !== $tenant->id) {
            abort(403);
        }

        if (! $motoboy->is_active) {
            auth()->guard('motoboy')->logout();

            return redirect()->route('tenant.entregador.login', ['tenant' => $tenant->slug])
                ->with('error', 'Sua conta está desativada.');
        }

        return $next($request);
    }
}
