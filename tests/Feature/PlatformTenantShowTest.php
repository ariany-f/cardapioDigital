<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTenantShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_can_view_tenant_by_id(): void
    {
        $plan = Plan::create([
            'name' => 'Básico',
            'slug' => 'basico',
            'price_monthly' => 99,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Restaurante X',
            'slug' => 'restaurante-x',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'is_platform_user' => true,
            'tenant_id' => null,
        ]);

        $this->actingAs($admin)
            ->get("/platform/tenants/{$tenant->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Tenants/Index')
                ->where('selectedTenant.id', $tenant->id));
    }
}
