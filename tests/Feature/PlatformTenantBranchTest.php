<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTenantBranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_user_can_manage_tenant_branches(): void
    {
        $tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        $admin = User::factory()->create(['is_platform_user' => true, 'tenant_id' => null]);

        $this->actingAs($admin)
            ->get("/platform/tenants/{$tenant->id}/branches")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/Tenants/Branches'));

        $this->actingAs($admin)
            ->post("/platform/tenants/{$tenant->id}/branches", [
                'name' => 'Zona Sul',
                'slug' => 'zona-sul',
                'city' => 'São Paulo',
                'is_active' => true,
                'pickup_available' => true,
                'delivery_available' => false,
            ])
            ->assertRedirect(route('platform.tenants.branches.index', $tenant));

        $branch = Branch::withoutGlobalScopes()->where('slug', 'zona-sul')->first();
        $this->assertNotNull($branch);
        $this->assertSame($tenant->id, $branch->tenant_id);

        $this->actingAs($admin)
            ->put("/platform/tenants/{$tenant->id}/branches/{$branch->id}", [
                'name' => 'ACME Zona Sul',
                'city' => 'São Paulo',
                'is_active' => true,
                'pickup_available' => true,
                'delivery_available' => true,
            ])
            ->assertRedirect(route('platform.tenants.branches.edit', [$tenant, $branch]));

        $this->assertSame('ACME Zona Sul', $branch->fresh()->name);
    }
}
