<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockProbePaths
{
    /** @var list<string> */
    protected array $blockedPrefixes = [
        '.env',
        '.git',
        'wp-admin',
        'wp-login',
        'wp-content',
        'wp-includes',
        'phpmyadmin',
        'pma',
        'adminer',
        'xmlrpc.php',
        'vendor/phpunit',
        'actuator',
        'telescope',
        '_ignition',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.block_probe_paths', true)) {
            return $next($request);
        }

        $path = strtolower(trim($request->path(), '/'));

        foreach ($this->blockedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/') || str_contains($path, '/'.$prefix)) {
                abort(404);
            }
        }

        if (str_contains($path, '..') || preg_match('/\.(sql|bak|old|log|ini)$/i', $path)) {
            abort(404);
        }

        return $next($request);
    }
}
