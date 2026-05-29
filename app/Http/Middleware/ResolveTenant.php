<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // Rotas do superadmin usam {tenant} como ID (model binding), não slug público
        if (! $request->routeIs('tenant.*')) {
            return $next($request);
        }

        $slug = $request->route('tenant');

        if (! $slug || ! is_string($slug)) {
            return $next($request);
        }

        $tenant = Tenant::query()->where('slug', $slug)->first();

        if (! $tenant) {
            abort(404);
        }

        TenantContext::set($tenant);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
