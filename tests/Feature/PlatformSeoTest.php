<?php

namespace Tests\Feature;

use App\Models\PlatformSeoSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SeoService;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformSeoTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlatformSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->platformUser = User::where('email', env('SEED_SUPERADMIN_EMAIL', 'admin@admin.com.br'))->first();
    }

    public function test_robots_and_sitemap_are_public(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap:', false);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset', false);
    }

    public function test_landing_includes_seo_meta(): void
    {
        PlatformSeoSetting::current()->update([
            'meta_title' => 'Título SEO Teste',
            'meta_description' => 'Descrição para buscadores',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Marketing/Landing')
                ->where('seo.title', 'Título SEO Teste')
                ->where('seo.description', 'Descrição para buscadores'));
    }

    public function test_platform_user_can_update_seo_settings(): void
    {
        $this->actingAs($this->platformUser)
            ->put(route('platform.settings.seo.update'), [
                'site_name' => 'App Cardápio',
                'meta_title' => 'Novo título SEO',
                'meta_description' => 'Nova descrição longa o suficiente para o Google.',
                'meta_keywords' => 'cardápio, pedidos',
                'robots_index' => true,
                'twitter_card' => 'summary_large_image',
                'json_ld_enabled' => true,
                'tenant_title_template' => '{name} — Delivery',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Novo título SEO', PlatformSeoSetting::current()->meta_title);
    }

    public function test_tenant_seo_overrides_defaults(): void
    {
        $tenant = Tenant::where('slug', 'acme')->first();

        $this->actingAs($this->platformUser)
            ->put(route('platform.tenants.seo.update', $tenant->id), [
                'meta_title' => 'ACME Lanches — Delivery Centro',
                'meta_description' => 'Peça pelo cardápio digital da ACME.',
                'robots_index' => true,
                'json_ld_enabled' => true,
            ])
            ->assertRedirect();

        $seo = app(SeoService::class)->forTenant($tenant->fresh());

        $this->assertSame('ACME Lanches — Delivery Centro', $seo['title']);
        $this->assertSame('Peça pelo cardápio digital da ACME.', $seo['description']);
    }

    public function test_tenant_home_uses_seo_service(): void
    {
        $tenant = Tenant::where('slug', 'acme')->first();
        $tenant->update([
            'seo_json' => [
                'meta_title' => 'ACME SEO Home',
                'robots_index' => true,
            ],
        ]);

        $this->get("/{$tenant->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('seo.title', 'ACME SEO Home'));
    }
}
