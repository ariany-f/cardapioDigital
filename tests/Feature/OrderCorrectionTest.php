<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('orders.accept');

        $this->tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        $this->branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);
        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@acme.test',
            'password' => Hash::make('password'),
            'tenant_id' => $this->tenant->id,
        ]);
        $this->admin->givePermissionTo('orders.accept');
    }

    public function test_admin_can_revert_payment_confirmation(): void
    {
        $order = $this->order(['payment_status' => 'paid', 'status' => 'preparing']);
        OrderPayment::create([
            'order_id' => $order->id,
            'gateway' => 'manual',
            'amount' => 50,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->post("/{$this->tenant->slug}/admin/orders/{$order->id}/revert-payment", [
                'reason' => 'Clique errado',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $order->payments()->first()->status);
    }

    public function test_admin_can_correct_status_from_delivered(): void
    {
        $order = $this->order([
            'type' => 'pickup',
            'status' => 'delivered',
            'delivery_confirmed_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->patch("/{$this->tenant->slug}/admin/orders/{$order->id}/correct-status", [
                'status' => 'ready',
                'reason' => 'Marcado entregue por engano',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('ready', $order->status);
        $this->assertNull($order->delivery_confirmed_at);
    }

    public function test_status_correction_requires_reason(): void
    {
        $order = $this->order(['status' => 'preparing']);

        $this->actingAs($this->admin)
            ->patch("/{$this->tenant->slug}/admin/orders/{$order->id}/correct-status", [
                'status' => 'ready',
                'reason' => 'ab',
            ])
            ->assertSessionHasErrors('reason');
    }

    protected function order(array $attrs = []): Order
    {
        return Order::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'ACME-'.random_int(1000, 9999),
            'type' => 'delivery',
            'status' => 'preparing',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 50,
            'total' => 50,
            'payment_status' => 'pending',
        ], $attrs));
    }
}
