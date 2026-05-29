<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpsInProduction
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            config('security.force_https')
            && ! $request->secure()
            && ! app()->runningInConsole()
            && ! app()->environment('testing')
        ) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
