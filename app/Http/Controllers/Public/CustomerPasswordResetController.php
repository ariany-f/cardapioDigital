<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerPasswordResetController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Conta/ForgotPassword', [
            'globalAccount' => request()->routeIs('app.conta.*'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::broker('customers')->sendResetLink($request->only('email'));

        return back()->with('success', 'Se o e-mail existir, enviaremos um link para redefinir a senha.');
    }

    public function edit(Request $request, string $token): Response
    {
        return Inertia::render('Conta/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
            'globalAccount' => request()->routeIs('app.conta.*'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                $customer->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($customer));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        $route = request()->routeIs('app.conta.*') ? 'app.conta.login' : 'tenant.conta.login';
        $params = request()->routeIs('app.conta.*') ? [] : ['tenant' => request()->route('tenant')];

        return redirect()->route($route, $params)->with('success', 'Senha redefinida. Faça login.');
    }
}
