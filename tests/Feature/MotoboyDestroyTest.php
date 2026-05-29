<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Delivery;
use App\Models\Motoboy;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DeliveryStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MotoboyDestroyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected User $admin;

    protected Motoboy $motoboy;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['orders.cancel', 'deliveries.update'] as $permission) {
            Permission::findOrCreate($permission);
        }

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
            'is_platform_user' => false,
        ]);
        $this->admin->givePermissionTo('orders.cancel', 'deliveries.update');

        $this->motoboy = Motoboy::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'João',
            'phone' => '11988887777',
            'vehicle_type' => 'motorcycle',
            'employment_type' => 'freelancer',
            'operational_status' => 'busy',
            'max_active_deliveries' => 2,
            'is_active' => true,
            'uses_app' => false,
        ]);
    }

    public function test_cannot_delete_motoboy_with_active_delivery(): void
    {
        $order = $this->deliveryOrder('out_for_delivery');
        app(DeliveryStatusService::class)->assignMotoboy($order, $this->motoboy->id);

        $this->actingAs($this->admin)
            ->delete(route('tenant.admin.motoboys.destroy', [
                'tenant' => $this->tenant->slug,
                'motoboy' => $this->motoboy->id,
            ]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertModelExists($this->motoboy);
    }

    public function test_cancelled_order_releases_motoboy_and_allows_delete(): void
    {
        $order = $this->deliveryOrder('out_for_delivery');
        $delivery = app(DeliveryStatusService::class)->assignMotoboy($order, $this->motoboy->id);
        $this->assertSame('assigned', $delivery->fresh()->status);

        $this->actingAs($this->admin)
            ->post("/{$this->tenant->slug}/admin/orders/{$order->id}/cancel", [
                'cancel_reason' => 'Cliente desistiu',
            ])
            ->assertRedirect();

        $order->refresh();
        $delivery->refresh();
        $this->motoboy->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('cancelled', $delivery->status);
        $this->assertSame('available', $this->motoboy->operational_status);

        $this->actingAs($this->admin)
            ->delete(route('tenant.admin.motoboys.destroy', [
                'tenant' => $this->tenant->slug,
                'motoboy' => $this->motoboy->id,
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertModelMissing($this->motoboy);
    }

    public function test_motoboy_can_be_deleted_when_only_stale_delivery_on_cancelled_order(): void
    {
        $order = $this->deliveryOrder('cancelled');
        Delivery::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'motoboy_id' => $this->motoboy->id,
            'status' => 'on_route',
            'motoboy_assignment_status' => 'accepted',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('tenant.admin.motoboys.destroy', [
                'tenant' => $this->tenant->slug,
                'motoboy' => $this->motoboy->id,
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertModelMissing($this->motoboy);
    }

    protected function deliveryOrder(string $status): Order
    {
        return Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'ACME-'.fake()->unique()->numerify('####'),
            'type' => 'delivery',
            'status' => $status,
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'delivery_address' => 'Rua Teste, 100',
            'subtotal' => 30,
            'total' => 30,
        ]);
    }
}
