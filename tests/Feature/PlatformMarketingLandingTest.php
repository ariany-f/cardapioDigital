<?php

namespace Tests\Feature;

use App\Models\PlatformSeoSetting;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformMarketingLandingTest extends TestCase
{
    use RefreshDatabase;

    protected User $platformUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlatformSeeder::class);

        $this->platformUser = User::where('email', env('SEED_SUPERADMIN_EMAIL', 'admin@admin.com.br'))->first();
    }

    public function test_landing_is_public_by_default(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Marketing/Landing'));
    }

    public function test_landing_redirects_to_login_when_disabled(): void
    {
        PlatformSeoSetting::current()->update(['marketing_landing_enabled' => false]);

        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_contact_form_disabled_when_landing_off(): void
    {
        PlatformSeoSetting::current()->update(['marketing_landing_enabled' => false]);

        $this->post('/contato', [
            'restaurant_name' => 'Bar',
            'contact_name' => 'João',
            'email' => 'joao@test.com',
        ])->assertNotFound();
    }

    public function test_platform_user_can_toggle_landing(): void
    {
        $this->actingAs($this->platformUser)
            ->from(route('platform.settings.seo'))
            ->put(route('platform.settings.seo.update'), [
                'site_name' => 'App Cardápio',
                'meta_title' => 'Título',
                'meta_description' => 'Descrição longa o suficiente',
                'robots_index' => false,
                'marketing_landing_enabled' => false,
                'twitter_card' => 'summary_large_image',
                'json_ld_enabled' => true,
                'tenant_title_template' => '{name} — Cardápio',
            ])
            ->assertRedirect();

        $this->assertFalse(PlatformSeoSetting::current()->marketing_landing_enabled);
    }
}
