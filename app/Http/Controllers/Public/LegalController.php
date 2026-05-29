<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use App\Support\TenantContext;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function __construct(protected SeoService $seo) {}

    public function terms(?string $tenant = null): Response
    {
        $tenantModel = $tenant ? TenantContext::get() : null;
        $titleSuffix = $tenantModel?->name ?? config('app.name', 'App Cardápio');

        $seo = $tenantModel
            ? $this->seo->forTenant($tenantModel, [
                'title' => __('legal.terms.title').' — '.$titleSuffix,
                'robots' => 'index, follow',
            ])
            : array_merge($this->seo->forMarketing(), [
                'title' => __('legal.terms.title').' — '.$titleSuffix,
                'robots' => 'index, follow',
            ]);

        return Inertia::render('Public/TermsOfService', [
            'seo' => $seo,
            'content' => [
                'title' => __('legal.terms.title'),
                'updated_at' => __('legal.terms.updated_at'),
                'intro' => __('legal.terms.intro'),
                'sections' => __('legal.terms.sections'),
            ],
            'tenantSlug' => $tenantModel?->slug,
        ]);
    }
}
