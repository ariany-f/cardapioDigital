<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSettingsPageTest extends TestCase
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

    public function test_order_settings_page_lists_branches(): void
    {
        $this->actingAs($this->admin)
            ->get(route('tenant.admin.orders.settings', ['tenant' => $this->tenant->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Orders/Settings')
                ->has('branches', 1)
                ->where('branches.0.auto_accept_orders', false));
    }

    public function test_order_settings_page_requires_orders_accept_permission(): void
    {
        $operator = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'op@acme.test',
        ]);
        $operator->assignRole('operator');

        $this->actingAs($operator)
            ->get(route('tenant.admin.orders.settings', ['tenant' => $this->tenant->slug]))
            ->assertOk();

        $viewer = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'view@acme.test',
        ]);
        $viewer->syncRoles([]);

        $this->actingAs($viewer)
            ->get(route('tenant.admin.orders.settings', ['tenant' => $this->tenant->slug]))
            ->assertForbidden();
    }
}
