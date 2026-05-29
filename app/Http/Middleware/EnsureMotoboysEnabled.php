<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use App\Support\TenantFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMotoboysEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::get();

        if ($tenant && ! TenantFeatures::motoboysEnabled($tenant)) {
            if ($request->routeIs('tenant.entregador.*')) {
                return redirect()
                    ->route('tenant.home', ['tenant' => $tenant->slug])
                    ->with('error', 'O módulo de entregadores está desativado para este restaurante.');
            }

            abort(403, 'O módulo de entregadores está desativado para este restaurante.');
        }

        return $next($request);
    }
}
