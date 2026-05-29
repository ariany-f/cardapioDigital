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

class OrderApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['orders.view', 'orders.accept', 'orders.cancel'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $this->tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        $this->branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
            'auto_accept_orders' => false,
        ]);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@acme.test',
            'password' => Hash::make('password'),
            'tenant_id' => $this->tenant->id,
            'is_platform_user' => false,
        ]);
        $this->admin->givePermissionTo('orders.view', 'orders.accept', 'orders.cancel');
    }

    public function test_admin_can_accept_pending_order(): void
    {
        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'ACME-0001',
            'type' => 'pickup',
            'status' => 'pending_approval',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 10,
            'total' => 10,
        ]);

        $this->actingAs($this->admin)
            ->post("/{$this->tenant->slug}/admin/orders/{$order->id}/accept")
            ->assertRedirect();

        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_cannot_accept_already_confirmed_order(): void
    {
        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'ACME-0002',
            'type' => 'pickup',
            'status' => 'confirmed',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 10,
            'total' => 10,
        ]);

        $this->actingAs($this->admin)
            ->post("/{$this->tenant->slug}/admin/orders/{$order->id}/accept")
            ->assertSessionHasErrors('order');
    }

    public function test_admin_can_cancel_order_with_reason(): void
    {
        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'ACME-0003',
            'type' => 'pickup',
            'status' => 'preparing',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 10,
            'total' => 10,
        ]);

        $this->actingAs($this->admin)
            ->post("/{$this->tenant->slug}/admin/orders/{$order->id}/cancel", [
                'cancel_reason' => 'Cliente desistiu',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('Cliente desistiu', $order->cancel_reason);
    }

    public function test_admin_can_reject_pending_order(): void
    {
        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'ACME-0004',
            'type' => 'pickup',
            'status' => 'pending_approval',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 10,
            'total' => 10,
        ]);

        $this->actingAs($this->admin)
            ->post("/{$this->tenant->slug}/admin/orders/{$order->id}/reject")
            ->assertRedirect();

        $this->assertSame('rejected', $order->fresh()->status);
    }

    public function test_branch_auto_accept_setting_can_be_toggled(): void
    {
        $this->assertFalse($this->branch->auto_accept_orders);

        $this->actingAs($this->admin)
            ->patch("/{$this->tenant->slug}/admin/branches/{$this->branch->id}/orders-status", [
                'auto_accept_orders' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($this->branch->fresh()->auto_accept_orders);
    }
}
