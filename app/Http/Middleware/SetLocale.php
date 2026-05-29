<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang')
            ?? $request->session()->get('locale')
            ?? config('app.locale');

        $allowed = \App\Models\Language::query()->where('is_active', true)->pluck('code')->all();
        if (! $allowed) {
            $allowed = ['pt_BR'];
        }

        if (in_array($locale, $allowed, true)) {
            App::setLocale($locale);
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }
}
