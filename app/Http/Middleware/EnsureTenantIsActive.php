<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = TenantContext::get();

        if (! $tenant) {
            return $next($request);
        }

        if ($tenant->status !== 'active') {
            $isPlatformAdmin = $request->user()?->is_platform_user && $request->is('*/admin/*');

            if (! $isPlatformAdmin) {
                return Inertia::render('Public/Suspended', [
                    'tenant' => $tenant->only('name', 'slug'),
                ])->toResponse($request);
            }
        }

        $subscription = $tenant->activeSubscription;

        if ($subscription && in_array($subscription->payment_status, ['overdue', 'pending'], true)) {
            $isPlatform = $request->user()?->is_platform_user;

            if (! $isPlatform && ! $request->is('*/admin/*')) {
                return Inertia::render('Public/Suspended', [
                    'tenant' => $tenant->only('name', 'slug'),
                    'reason' => 'subscription',
                ])->toResponse($request);
            }
        }

        return $next($request);
    }
}
