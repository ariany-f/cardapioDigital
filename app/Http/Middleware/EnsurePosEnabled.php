<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use App\Support\TenantFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePosEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::get();

        if ($tenant && ! TenantFeatures::posEnabled($tenant)) {
            abort(403, 'O PDV está desativado para este restaurante.');
        }

        return $next($request);
    }
}
