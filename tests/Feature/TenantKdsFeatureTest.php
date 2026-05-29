<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantFeatures;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantKdsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
    }

    public function test_kds_enabled_by_default(): void
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);

        $this->assertTrue(TenantFeatures::kdsEnabled($tenant));
    }

    public function test_platform_can_disable_kds_for_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);
        $admin = User::factory()->create(['is_platform_user' => true, 'tenant_id' => null]);

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'name' => 'Loja',
                'slug' => 'loja',
                'kds_enabled' => false,
            ])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertFalse(TenantFeatures::kdsEnabled($tenant));
    }

    public function test_admin_kds_page_blocked_when_feature_disabled(): void
    {
        $tenant = Tenant::create([
            'name' => 'Loja',
            'slug' => 'loja',
            'status' => 'active',
            'settings_json' => ['kds_enabled' => false],
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->givePermissionTo('kds.access');

        $this->actingAs($user)
            ->get(route('tenant.admin.kds', ['tenant' => $tenant->slug]))
            ->assertForbidden();
    }
}
