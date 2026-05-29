<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSeoSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSeoSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = PlatformSeoSetting::current();

        return Inertia::render('Platform/Settings/Seo', [
            'settings' => $settings->toFormArray(),
            'defaults' => [
                'canonical_url' => url('/'),
                'tenant_title_template' => '{name} — Cardápio e pedidos online',
            ],
            'hints' => [
                'title_length' => 60,
                'description_length' => 160,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'meta_title' => ['required', 'string', 'max:120'],
            'meta_description' => ['required', 'string', 'max:320'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots_index' => ['boolean'],
            'marketing_landing_enabled' => ['boolean'],
            'og_title' => ['nullable', 'string', 'max:120'],
            'og_description' => ['nullable', 'string', 'max:320'],
            'og_image_path' => ['nullable', 'string', 'max:500'],
            'twitter_card' => ['required', Rule::in(['summary', 'summary_large_image'])],
            'google_site_verification' => ['nullable', 'string', 'max:100'],
            'google_analytics_id' => ['nullable', 'string', 'max:50'],
            'json_ld_enabled' => ['boolean'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'organization_logo_path' => ['nullable', 'string', 'max:500'],
            'tenant_title_template' => ['required', 'string', 'max:120'],
            'tenant_meta_description_fallback' => ['nullable', 'string', 'max:320'],
            'tenant_og_image_path' => ['nullable', 'string', 'max:500'],
        ]);

        PlatformSeoSetting::current()->update([
            ...$data,
            'robots_index' => $request->boolean('robots_index'),
            'marketing_landing_enabled' => $request->boolean('marketing_landing_enabled'),
            'json_ld_enabled' => $request->boolean('json_ld_enabled'),
        ]);

        return back()->with('success', 'Configurações de SEO salvas.');
    }
}
