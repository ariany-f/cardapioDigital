<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Plan;
use App\Models\PlatformSeoSetting;
use App\Models\Tenant;
use App\Support\MediaUrl;
use App\Support\PlatformMarketing;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeoService
{
    public function settings(): PlatformSeoSetting
    {
        if (! Schema::hasTable('platform_seo_settings')) {
            return new PlatformSeoSetting([
                'site_name' => config('app.name', 'App Cardápio'),
                'robots_index' => true,
                'twitter_card' => 'summary_large_image',
                'json_ld_enabled' => true,
            ]);
        }

        return PlatformSeoSetting::current();
    }

    /**
     * @return array<string, mixed>
     */
    public function forMarketing(?Plan $plan = null): array
    {
        $s = $this->settings();
        $canonical = $s->canonical_url ?: url('/');

        $title = $s->meta_title ?: $s->site_name;
        $description = $s->meta_description ?: '';
        $ogTitle = $s->og_title ?: $title;
        $ogDescription = $s->og_description ?: $description;
        $ogImage = MediaUrl::fromPath($s->og_image_path);

        return $this->buildMeta([
            'title' => $title,
            'description' => $description,
            'keywords' => $s->meta_keywords,
            'canonical' => $canonical,
            'robots' => $s->robots_index ? 'index, follow' : 'noindex, nofollow',
            'og' => [
                'title' => $ogTitle,
                'description' => $ogDescription,
                'image' => $ogImage,
                'url' => $canonical,
                'type' => 'website',
                'site_name' => $s->site_name,
            ],
            'twitter' => [
                'card' => $s->twitter_card ?: 'summary_large_image',
                'title' => $ogTitle,
                'description' => $ogDescription,
                'image' => $ogImage,
            ],
            'verification' => [
                'google' => $s->google_site_verification,
            ],
            'analytics' => $s->google_analytics_id,
            'json_ld' => $s->json_ld_enabled ? $this->marketingJsonLd($s, $plan) : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function forTenant(Tenant $tenant, array $overrides = []): array
    {
        $s = $this->settings();
        $seo = is_array($tenant->seo_json) ? $tenant->seo_json : [];

        $title = $overrides['title']
            ?? $seo['meta_title']
            ?? str_replace('{name}', $tenant->name, $s->tenant_title_template ?: '{name}');

        $description = $overrides['description']
            ?? $seo['meta_description']
            ?? $tenant->public_description
            ?? $s->tenant_meta_description_fallback
            ?? '';

        $description = Str::limit(strip_tags($description), 320, '');

        $canonical = $overrides['canonical']
            ?? $seo['canonical_url']
            ?? url('/'.$tenant->slug);

        $robotsIndex = array_key_exists('robots_index', $seo)
            ? (bool) $seo['robots_index']
            : true;

        $robots = $overrides['robots'] ?? null;

        $ogImage = MediaUrl::fromPath(
            $overrides['og_image']
                ?? $seo['og_image_path']
                ?? $tenant->cover_image_path
                ?? $s->tenant_og_image_path
                ?? $s->og_image_path
        );

        $analytics = $seo['google_analytics_id'] ?? $s->google_analytics_id;

        return $this->buildMeta([
            'title' => $title,
            'description' => $description,
            'keywords' => $seo['meta_keywords'] ?? null,
            'canonical' => $canonical,
            'robots' => $robots ?? ($robotsIndex ? 'index, follow' : 'noindex, nofollow'),
            'og' => [
                'title' => $title,
                'description' => $description,
                'image' => $ogImage,
                'url' => $canonical,
                'type' => 'website',
                'site_name' => $tenant->name,
            ],
            'twitter' => [
                'card' => $s->twitter_card ?: 'summary_large_image',
                'title' => $title,
                'description' => $description,
                'image' => $ogImage,
            ],
            'verification' => [
                'google' => $seo['google_site_verification'] ?? $s->google_site_verification,
            ],
            'analytics' => $analytics,
            'json_ld' => ($robots && str_contains((string) $robots, 'noindex'))
                ? null
                : (($seo['json_ld_enabled'] ?? true)
                    ? $this->restaurantJsonLd($tenant, $canonical, $ogImage)
                    : null),
        ]);
    }

    public function forBranch(Tenant $tenant, Branch $branch): array
    {
        $branchTitle = $branch->name.' — '.$tenant->name;
        $description = $branch->public_description
            ?? $tenant->public_description
            ?? '';

        return $this->forTenant($tenant, [
            'title' => $branchTitle,
            'description' => $description,
            'canonical' => url('/'.$tenant->slug.'/'.$branch->slug),
            'og_image' => $branch->cover_image_path ?? $tenant->cover_image_path,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildMeta(array $data): array
    {
        return [
            'title' => $data['title'] ?? config('app.name'),
            'description' => $data['description'] ?? '',
            'keywords' => $data['keywords'] ?? null,
            'canonical' => $data['canonical'] ?? url('/'),
            'robots' => $data['robots'] ?? 'index, follow',
            'og' => $data['og'] ?? [],
            'twitter' => $data['twitter'] ?? [],
            'verification' => $data['verification'] ?? [],
            'analytics' => $data['analytics'] ?? null,
            'json_ld' => $data['json_ld'] ?? null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function marketingJsonLd(PlatformSeoSetting $s, ?Plan $plan): array
    {
        $graphs = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $s->organization_name ?: $s->site_name,
                'url' => $s->canonical_url ?: url('/'),
                'logo' => MediaUrl::fromPath($s->organization_logo_path ?: $s->og_image_path),
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $s->site_name,
                'url' => $s->canonical_url ?: url('/'),
            ],
        ];

        if ($plan) {
            $graphs[] = [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => $s->site_name,
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => (string) $plan->price_monthly,
                    'priceCurrency' => 'BRL',
                ],
            ];
        }

        return $graphs;
    }

    /**
     * @return array<string, mixed>
     */
    protected function restaurantJsonLd(Tenant $tenant, string $url, ?string $image): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            'name' => $tenant->name,
            'url' => $url,
            'description' => Str::limit(strip_tags($tenant->public_description ?? ''), 300, ''),
        ];

        if ($image) {
            $data['image'] = $image;
        }

        if ($tenant->city) {
            $data['address'] = [
                '@type' => 'PostalAddress',
                'addressLocality' => $tenant->city,
                'addressRegion' => $tenant->state,
                'addressCountry' => 'BR',
            ];
        }

        if ($tenant->phone) {
            $data['telephone'] = $tenant->phone;
        }

        return $data;
    }

    public function sitemapUrls(): array
    {
        $urls = [];
        $s = $this->settings();

        if ($s->robots_index && PlatformMarketing::landingEnabled()) {
            $urls[] = [
                'loc' => $s->canonical_url ?: url('/'),
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ];
        }

        if (! Schema::hasTable('tenants')) {
            return $urls;
        }

        Tenant::query()
            ->where('status', 'active')
            ->with(['branches' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->each(function (Tenant $tenant) use (&$urls) {
                $seo = is_array($tenant->seo_json) ? $tenant->seo_json : [];
                if (array_key_exists('robots_index', $seo) && ! $seo['robots_index']) {
                    return;
                }

                $urls[] = [
                    'loc' => url('/'.$tenant->slug),
                    'changefreq' => 'daily',
                    'priority' => '0.8',
                ];

                foreach ($tenant->branches as $branch) {
                    $urls[] = [
                        'loc' => url('/'.$tenant->slug.'/'.$branch->slug),
                        'changefreq' => 'daily',
                        'priority' => '0.9',
                    ];
                }
            });

        return $urls;
    }
}
