<?php

namespace App\Http\Controllers\Entregador;

use App\Http\Controllers\Controller;
use App\Models\Motoboy;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MotoboyAuthController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Entregador/Login', [
            'tenant' => TenantContext::get()?->only(['name', 'slug']),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $tenant = TenantContext::get();

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $motoboy = Motoboy::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $credentials['email'])
            ->where('is_active', true)
            ->first();

        if (! $motoboy || ! $motoboy->usesApp()) {
            throw ValidationException::withMessages([
                'email' => ['Este entregador não possui acesso ao painel web. Peça ao restaurante para habilitar o login.'],
            ]);
        }

        if (! $motoboy->password || ! Hash::check($credentials['password'], $motoboy->password)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha incorretos.'],
            ]);
        }

        Auth::guard('motoboy')->login($motoboy, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('tenant.entregador.dashboard', ['tenant' => $tenant->slug]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $tenant = TenantContext::get();

        Auth::guard('motoboy')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.entregador.login', ['tenant' => $tenant->slug]);
    }
}
