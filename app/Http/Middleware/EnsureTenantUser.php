<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = TenantContext::get();

        if ($user?->is_platform_user) {
            return $next($request);
        }

        if (! $tenant || $user?->tenant_id !== $tenant->id) {
            abort(403);
        }

        return $next($request);
    }
}
