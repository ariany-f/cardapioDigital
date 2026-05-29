<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Mail\PlatformMailTestMail;
use App\Models\PlatformMailSetting;
use App\Services\Mail\MailDispatcher;
use App\Services\Mail\PlatformMailConfigurator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlatformMailSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = PlatformMailSetting::current();

        return Inertia::render('Platform/Settings/Email', [
            'settings' => $settings->toPublicArray(),
            'envFallback' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'from_address' => config('mail.from.address'),
            ],
            'suppressesInLocal' => app()->environment('local') && config('mail.send_in_local') !== true,
        ]);
    }

    public function update(Request $request, PlatformMailConfigurator $configurator): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['boolean'],
            'host' => ['nullable', 'string', 'max:255', 'required_if:enabled,true'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['nullable', 'string', Rule::in(['', 'tls', 'ssl'])],
            'from_address' => ['nullable', 'email', 'max:255', 'required_if:enabled,true'],
            'from_name' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = PlatformMailSetting::current();
        $enabled = $request->boolean('enabled');

        if ($enabled && ! filled($data['password'] ?? null) && ! $settings->hasPassword()) {
            return back()->withErrors([
                'password' => 'Informe a senha SMTP para ativar o envio.',
            ]);
        }

        $encryption = $data['encryption'] ?? '';
        $encryption = $encryption === '' ? null : $encryption;

        $payload = [
            'enabled' => $enabled,
            'host' => $data['host'] ?? null,
            'port' => (int) ($data['port'] ?? 587),
            'username' => $data['username'] ?? null,
            'encryption' => $encryption,
            'from_address' => $data['from_address'] ?? null,
            'from_name' => $data['from_name'] ?? null,
        ];

        if (filled($data['password'] ?? null)) {
            $payload['password'] = $data['password'];
        }

        $settings->update($payload);
        $configurator->apply();

        return back()->with('success', 'Configuração de e-mail salva.');
    }

    public function sendTest(Request $request, MailDispatcher $mail, PlatformMailConfigurator $configurator): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['nullable', 'email', 'max:255'],
        ]);

        $settings = PlatformMailSetting::current();

        if (! $settings->enabled) {
            return back()->withErrors([
                'test' => 'Ative e salve a configuração SMTP antes de enviar o teste.',
            ]);
        }

        $configurator->apply();

        $to = $data['to'] ?? $request->user()->email;

        try {
            $mail->sendNow($to, new PlatformMailTestMail($request->user()->name), force: true);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'test' => 'Falha ao enviar: '.$e->getMessage(),
            ]);
        }

        return back()->with('success', "E-mail de teste enviado para {$to}.");
    }
}
