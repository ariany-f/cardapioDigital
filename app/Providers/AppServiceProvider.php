<?php

namespace App\Providers;

use App\Services\Mail\PlatformMailConfigurator;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->warnIfDebugInProduction();

        $this->app->make(PlatformMailConfigurator::class)->apply();

        Vite::prefetch(concurrency: 3);

        Authenticate::redirectUsing(function ($request) {
            if ($request->routeIs('tenant.conta.*')) {
                return route('tenant.conta.login', ['tenant' => $request->route('tenant')]);
            }

            return route('login');
        });

        RedirectIfAuthenticated::redirectUsing(function ($request) {
            if ($request->routeIs('tenant.conta.login', 'tenant.conta.register')) {
                return route('tenant.conta.dashboard', ['tenant' => $request->route('tenant')]);
            }

            return route('dashboard');
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            $email = (string) $request->input('email', '');

            return Limit::perMinute(10)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('checkout', function (Request $request) {
            $tenant = (string) $request->route('tenant', 'global');

            return Limit::perMinute(20)->by($tenant.'|'.$request->ip());
        });

        RateLimiter::for('chat', function (Request $request) {
            $tenant = (string) $request->route('tenant', 'global');

            return Limit::perMinute(40)->by($tenant.'|'.$request->ip());
        });

        RateLimiter::for('public-forms', function (Request $request) {
            $tenant = (string) $request->route('tenant', 'global');

            return Limit::perMinute(15)->by($tenant.'|'.$request->ip());
        });

        RateLimiter::for('webhook', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }

    protected function warnIfDebugInProduction(): void
    {
        if ($this->app->environment('production') && config('app.debug')) {
            Log::warning('APP_DEBUG está ativo em produção. Desative para não expor dados sensíveis em erros.');
        }
    }
}
