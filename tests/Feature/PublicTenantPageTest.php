<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTenantPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_lists_active_branches_when_multiple(): void
    {
        $tenant = Tenant::create([
            'name' => 'Restaurante Teste',
            'slug' => 'teste',
            'status' => 'active',
        ]);

        foreach (['centro', 'norte'] as $slug) {
            Branch::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'name' => ucfirst($slug),
                'slug' => $slug,
                'is_active' => true,
                'city' => 'São Paulo',
            ]);
        }

        $this->get('/teste')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Home')
                ->where('tenantName', 'Restaurante Teste')
                ->has('branches', 2));
    }

    public function test_public_home_redirects_to_branch_when_single_unit(): void
    {
        $tenant = Tenant::create([
            'name' => 'Loja Única',
            'slug' => 'unica',
            'status' => 'active',
        ]);

        Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);

        $this->get('/unica')
            ->assertRedirect(route('tenant.branch', ['tenant' => 'unica', 'branch' => 'centro']));
    }
}
