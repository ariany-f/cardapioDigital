<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminOrdersPendingCountTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('orders.view');

        $this->tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);
        $this->branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@loja.test',
            'password' => Hash::make('password'),
            'tenant_id' => $this->tenant->id,
        ]);
        $this->admin->givePermissionTo('orders.view');
    }

    public function test_pending_count_returns_orders_awaiting_approval(): void
    {
        foreach (['LOJA-0001', 'LOJA-0002'] as $number) {
            Order::withoutGlobalScopes()->create([
                'tenant_id' => $this->tenant->id,
                'branch_id' => $this->branch->id,
                'order_number' => $number,
                'type' => 'pickup',
                'status' => 'pending_approval',
                'guest_name' => 'Cliente',
                'guest_phone' => '11999999999',
                'subtotal' => 10,
                'total' => 10,
            ]);
        }
        Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'LOJA-0003',
            'type' => 'pickup',
            'status' => 'confirmed',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 30,
            'total' => 30,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('tenant.admin.orders.pending-count', ['tenant' => $this->tenant->slug]))
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_pending_count_excludes_orders_without_view_permission(): void
    {
        $this->admin->revokePermissionTo('orders.view');

        $this->actingAs($this->admin)
            ->getJson(route('tenant.admin.orders.pending-count', ['tenant' => $this->tenant->slug]))
            ->assertForbidden();
    }
}
