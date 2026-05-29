<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Delivery;
use App\Models\DeliveryStatusHistory;
use App\Models\Motoboy;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantFeatures;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderShowDeliveryHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);

        $this->tenant = Tenant::where('slug', 'acme')->firstOrFail();
        $this->branch = Branch::withoutGlobalScopes()
            ->where('tenant_id', $this->tenant->id)
            ->firstOrFail();

        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->admin->givePermissionTo('orders.view');
    }

    public function test_order_show_includes_motoboy_when_module_disabled(): void
    {
        TenantFeatures::setMotoboysEnabled($this->tenant, false);
        $this->tenant->refresh();

        $motoboy = Motoboy::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'João Entregador',
            'phone' => '11988887777',
            'vehicle_type' => 'motorcycle',
            'employment_type' => 'freelancer',
            'is_active' => true,
            'uses_app' => false,
        ]);

        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'ACME-HIST-1',
            'type' => 'delivery',
            'status' => 'delivered',
            'subtotal' => 40,
            'total' => 40,
        ]);

        $delivery = Delivery::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'motoboy_id' => $motoboy->id,
            'status' => 'delivered',
            'motoboy_assignment_status' => 'accepted',
        ]);

        DeliveryStatusHistory::create([
            'delivery_id' => $delivery->id,
            'status' => 'assigned',
            'origin' => 'admin',
        ]);
        DeliveryStatusHistory::create([
            'delivery_id' => $delivery->id,
            'status' => 'delivered',
            'origin' => 'admin',
        ]);

        $this->actingAs($this->admin)
            ->get(route('tenant.admin.orders.show', ['tenant' => $this->tenant->slug, 'order' => $order->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Orders/Show')
                ->where('motoboys_enabled', false)
                ->where('order.delivery.motoboy.name', 'João Entregador')
                ->has('order.delivery.status_histories', 2)
                ->where('order.delivery.status_histories.0.status', 'assigned'));
    }
}
