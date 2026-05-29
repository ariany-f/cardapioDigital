<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchInstagramTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_page_shows_branch_instagram_when_set(): void
    {
        $tenant = Tenant::create([
            'name' => 'ACME',
            'slug' => 'acme',
            'status' => 'active',
            'social_links' => ['instagram' => 'marca'],
        ]);

        Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
            'instagram' => 'filialcentro',
        ]);

        $this->get('/acme/centro')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Branch')
                ->where('branch.instagram.handle', 'filialcentro')
                ->where('branch.instagram.url', 'https://www.instagram.com/filialcentro'));
    }

    public function test_branch_page_falls_back_to_tenant_instagram(): void
    {
        $tenant = Tenant::create([
            'name' => 'ACME',
            'slug' => 'acme',
            'status' => 'active',
            'social_links' => ['instagram' => 'marcaoficial'],
        ]);

        Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);

        $this->get('/acme/centro')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('branch.instagram.handle', 'marcaoficial'));
    }

    public function test_branch_page_hides_instagram_when_none_set(): void
    {
        $tenant = Tenant::create([
            'name' => 'ACME',
            'slug' => 'acme',
            'status' => 'active',
        ]);

        Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);

        $this->get('/acme/centro')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('branch.instagram', null));
    }
}
