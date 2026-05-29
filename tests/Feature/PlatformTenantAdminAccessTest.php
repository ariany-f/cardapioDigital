<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTenantAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_user_can_access_tenant_admin_dashboard(): void
    {
        $tenant = $this->createTenant();

        $admin = User::factory()->create([
            'is_platform_user' => true,
            'tenant_id' => null,
        ]);

        $this->actingAs($admin)
            ->get("/{$tenant->slug}/admin")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Dashboard'));
    }

    public function test_platform_user_bypasses_plan_feature_on_kds(): void
    {
        $tenant = $this->createTenant();

        $admin = User::factory()->create([
            'is_platform_user' => true,
            'tenant_id' => null,
        ]);

        $this->actingAs($admin)
            ->get("/{$tenant->slug}/admin/kds")
            ->assertOk();
    }

    public function test_platform_user_can_access_admin_when_tenant_suspended(): void
    {
        $tenant = $this->createTenant();
        $tenant->update(['status' => 'suspended']);

        $admin = User::factory()->create([
            'is_platform_user' => true,
            'tenant_id' => null,
        ]);

        $this->actingAs($admin)
            ->get("/{$tenant->slug}/admin/orders")
            ->assertOk();
    }

    protected function createTenant(): Tenant
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

        $tenant->subscriptions()->create([
            'plan_id' => $plan->id,
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
            'status' => 'active',
            'payment_status' => 'paid',
        ]);

        return $tenant;
    }
}
