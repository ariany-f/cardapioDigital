<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformGoogleMapsSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformGoogleMapsSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = PlatformGoogleMapsSetting::current();

        return Inertia::render('Platform/Settings/Maps', [
            'settings' => $settings->toAdminArray(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['boolean'],
            'api_key' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = PlatformGoogleMapsSetting::current();
        $enabled = $request->boolean('enabled');

        if ($enabled && ! filled($data['api_key'] ?? null) && ! $settings->hasApiKey()) {
            return back()->withErrors([
                'api_key' => 'Informe a chave da API do Google Maps para ativar.',
            ]);
        }

        $payload = ['enabled' => $enabled];

        if (filled($data['api_key'] ?? null)) {
            $payload['api_key'] = trim($data['api_key']);
        }

        $settings->update($payload);

        return back()->with('success', 'Configuração do Google Maps salva.');
    }
}
