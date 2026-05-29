<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTenantDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_delete_tenant(): void
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

        TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
            'payment_status' => 'paid',
            'status' => 'active',
        ]);

        Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'is_platform_user' => true,
            'tenant_id' => null,
        ]);

        $this->actingAs($admin)
            ->delete(route('platform.tenants.destroy', $tenant))
            ->assertRedirect(route('platform.tenants.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('tenants', ['id' => $tenant->id]);
        $this->assertDatabaseMissing('branches', ['tenant_id' => $tenant->id]);
        $this->assertDatabaseMissing('tenant_subscriptions', ['tenant_id' => $tenant->id]);
    }

    public function test_tenant_user_cannot_delete_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'Restaurante Y',
            'slug' => 'restaurante-y',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'is_platform_user' => false,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user)
            ->delete(route('platform.tenants.destroy', $tenant))
            ->assertForbidden();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }
}
