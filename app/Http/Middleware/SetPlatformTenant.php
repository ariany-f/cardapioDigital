<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPlatformTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        if ($tenant instanceof Tenant) {
            TenantContext::set($tenant);
            $request->attributes->set('tenant', $tenant);
        }

        return $next($request);
    }
}
