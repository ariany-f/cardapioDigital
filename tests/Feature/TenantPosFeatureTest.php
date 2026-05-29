<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantFeatures;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantPosFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
    }

    public function test_pos_enabled_by_default(): void
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);

        $this->assertTrue(TenantFeatures::posEnabled($tenant));
    }

    public function test_platform_can_disable_pos_for_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);
        $admin = User::factory()->create(['is_platform_user' => true, 'tenant_id' => null]);

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'name' => 'Loja',
                'slug' => 'loja',
                'pos_enabled' => false,
            ])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertFalse(TenantFeatures::posEnabled($tenant));
    }

    public function test_admin_pos_page_blocked_when_feature_disabled(): void
    {
        $tenant = Tenant::create([
            'name' => 'Loja',
            'slug' => 'loja',
            'status' => 'active',
            'settings_json' => ['pos_enabled' => false],
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->givePermissionTo('orders.pos');

        $this->actingAs($user)
            ->get(route('tenant.admin.pos', ['tenant' => $tenant->slug]))
            ->assertForbidden();
    }
}
