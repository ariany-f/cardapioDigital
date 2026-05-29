<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSeoSetting extends Model
{
    protected $fillable = [
        'site_name',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'robots_index',
        'marketing_landing_enabled',
        'og_title',
        'og_description',
        'og_image_path',
        'twitter_card',
        'google_site_verification',
        'google_analytics_id',
        'json_ld_enabled',
        'organization_name',
        'organization_logo_path',
        'tenant_title_template',
        'tenant_meta_description_fallback',
        'tenant_og_image_path',
    ];

    protected function casts(): array
    {
        return [
            'robots_index' => 'boolean',
            'marketing_landing_enabled' => 'boolean',
            'json_ld_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'site_name' => 'App Cardápio',
            'meta_title' => 'App Cardápio — Cardápio digital e pedidos online para restaurantes',
            'meta_description' => 'Sistema de cardápio digital, pedidos, delivery, KDS e PIX para restaurantes. Plano acessível, sem taxa por pedido.',
            'meta_keywords' => 'cardápio digital, pedidos online, delivery, restaurante, KDS, PIX',
            'robots_index' => true,
            'marketing_landing_enabled' => true,
            'twitter_card' => 'summary_large_image',
            'json_ld_enabled' => true,
            'organization_name' => 'App Cardápio',
            'tenant_title_template' => '{name} — Cardápio e pedidos online',
        ]);
    }

    public function toFormArray(): array
    {
        return $this->only([
            'site_name',
            'meta_title',
            'meta_description',
            'meta_keywords',
            'canonical_url',
            'robots_index',
            'marketing_landing_enabled',
            'og_title',
            'og_description',
            'og_image_path',
            'twitter_card',
            'google_site_verification',
            'google_analytics_id',
            'json_ld_enabled',
            'organization_name',
            'organization_logo_path',
            'tenant_title_template',
            'tenant_meta_description_fallback',
            'tenant_og_image_path',
        ]);
    }
}
