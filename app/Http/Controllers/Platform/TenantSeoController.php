<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantSeoController extends Controller
{
    public function edit(Tenant $tenant, SeoService $seo): Response
    {
        $preview = $seo->forTenant($tenant);

        return Inertia::render('Platform/Tenants/Seo', [
            'tenant' => $tenant->only(['id', 'name', 'slug', 'public_description', 'cover_image_path']),
            'seo' => $tenant->seo_json ?? [],
            'preview' => $preview,
            'titleTemplate' => $seo->settings()->tenant_title_template,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:120'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'og_image_path' => ['nullable', 'string', 'max:500'],
            'robots_index' => ['boolean'],
            'google_site_verification' => ['nullable', 'string', 'max:100'],
            'google_analytics_id' => ['nullable', 'string', 'max:50'],
            'json_ld_enabled' => ['boolean'],
        ]);

        $tenant->update([
            'seo_json' => [
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
                'canonical_url' => $data['canonical_url'] ?? null,
                'og_image_path' => $data['og_image_path'] ?? null,
                'robots_index' => $request->boolean('robots_index', true),
                'google_site_verification' => $data['google_site_verification'] ?? null,
                'google_analytics_id' => $data['google_analytics_id'] ?? null,
                'json_ld_enabled' => $request->boolean('json_ld_enabled', true),
            ],
        ]);

        return back()->with('success', 'SEO do restaurante atualizado.');
    }
}
