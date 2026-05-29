<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformStorageSetting;
use App\Services\Storage\PlatformStorageConfigurator;
use App\Support\PlatformStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PlatformStorageSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = PlatformStorageSetting::current();

        return Inertia::render('Platform/Settings/Storage', [
            'settings' => $settings->toPublicArray(),
            'envFallback' => [
                'disk' => config('filesystems.default'),
                'bucket' => config('filesystems.disks.s3.bucket'),
                'region' => config('filesystems.disks.s3.region'),
            ],
            'activeDisk' => PlatformStorage::uploadDisk(),
        ]);
    }

    public function update(Request $request, PlatformStorageConfigurator $configurator): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['boolean'],
            'key' => ['nullable', 'string', 'max:255', 'required_if:enabled,true'],
            'secret' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:32', 'required_if:enabled,true'],
            'bucket' => ['nullable', 'string', 'max:255', 'required_if:enabled,true'],
            'url' => ['nullable', 'string', 'max:500'],
            'endpoint' => ['nullable', 'string', 'max:500'],
            'use_path_style_endpoint' => ['boolean'],
        ]);

        $settings = PlatformStorageSetting::current();
        $enabled = $request->boolean('enabled');

        if ($enabled && ! filled($data['secret'] ?? null) && ! $settings->hasSecret()) {
            return back()->withErrors([
                'secret' => 'Informe a secret key para ativar o S3.',
            ]);
        }

        $payload = [
            'enabled' => $enabled,
            'key' => $data['key'] ?? null,
            'region' => $data['region'] ?? 'us-east-1',
            'bucket' => $data['bucket'] ?? null,
            'url' => $data['url'] ?? null,
            'endpoint' => filled($data['endpoint'] ?? null) ? $data['endpoint'] : null,
            'use_path_style_endpoint' => $request->boolean('use_path_style_endpoint'),
        ];

        if (filled($data['secret'] ?? null)) {
            $payload['secret'] = $data['secret'];
        }

        $settings->update($payload);
        $configurator->apply();

        return back()->with('success', 'Configuração de armazenamento salva.');
    }

    public function testConnection(PlatformStorageConfigurator $configurator): RedirectResponse
    {
        $settings = PlatformStorageSetting::current();

        if (! $settings->isConfigured()) {
            return back()->withErrors([
                'test' => 'Ative e salve a configuração S3 antes de testar a conexão.',
            ]);
        }

        $configurator->apply();

        $path = 'platform-test/'.Str::uuid().'.txt';

        try {
            Storage::disk('s3')->put($path, 'ok-'.now()->toIso8601String(), 'private');
            Storage::disk('s3')->delete($path);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'test' => 'Falha na conexão: '.$e->getMessage(),
            ]);
        }

        return back()->with('success', 'Conexão com o bucket S3 verificada com sucesso.');
    }
}
