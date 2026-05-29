<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlatformSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->tenant = Tenant::where('slug', 'acme')->first();
        $this->admin = User::where('email', 'admin@acme.test')->first();
    }

    public function test_users_page_includes_role_permission_descriptions(): void
    {
        $this->actingAs($this->admin)
            ->get(route('tenant.admin.users.index', ['tenant' => $this->tenant->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/Index')
                ->has('rolePermissions.tenant_admin.areas')
                ->has('rolePermissions.manager.areas')
                ->where('rolePermissions.viewer.summary', fn ($summary) => str_contains($summary, 'leitura'))
                ->where('rolePermissions.operator.areas', fn ($areas) => collect($areas)->contains('PDV')
                    && ! collect($areas)->contains('Usuários'))
                ->where('rolePermissions.manager.areas', fn ($areas) => collect($areas)->contains('Produtos')
                    && ! collect($areas)->contains('Usuários')));
    }
}
