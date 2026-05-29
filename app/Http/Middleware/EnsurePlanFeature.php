<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use App\Support\TenantPlanFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if ($request->user()?->is_platform_user) {
            return $next($request);
        }

        $tenant = TenantContext::get();

        if ($tenant && ! TenantPlanFeatures::has($tenant, $feature)) {
            abort(403, 'Recurso não disponível no seu plano.');
        }

        return $next($request);
    }
}
