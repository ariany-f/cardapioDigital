<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Antes do Inertia (share roda no início do handle) — senão page.props.tenant fica null
        $middleware->web(prepend: [
            \App\Http\Middleware\BlockProbePaths::class,
            \App\Http\Middleware\ForceHttpsInProduction::class,
            \App\Http\Middleware\ResolveTenant::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'tenant' => \App\Http\Middleware\ResolveTenant::class,
            'tenant.active' => \App\Http\Middleware\EnsureTenantIsActive::class,
            'platform' => \App\Http\Middleware\EnsurePlatformUser::class,
            'platform.tenant' => \App\Http\Middleware\SetPlatformTenant::class,
            'plan.feature' => \App\Http\Middleware\EnsurePlanFeature::class,
            'tenant.user' => \App\Http\Middleware\EnsureTenantUser::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'motoboy.tenant' => \App\Http\Middleware\EnsureMotoboyTenant::class,
            'motoboys.enabled' => \App\Http\Middleware\EnsureMotoboysEnabled::class,
            'pos.enabled' => \App\Http\Middleware\EnsurePosEnabled::class,
            'kds.enabled' => \App\Http\Middleware\EnsureKdsEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
