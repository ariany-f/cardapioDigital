<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use App\Support\TenantFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKdsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::get();

        if ($tenant && ! TenantFeatures::kdsEnabled($tenant)) {
            abort(403, 'O KDS está desativado para este restaurante.');
        }

        return $next($request);
    }
}
