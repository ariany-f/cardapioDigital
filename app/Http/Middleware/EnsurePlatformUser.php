<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_platform_user) {
            abort(403);
        }

        return $next($request);
    }
}
