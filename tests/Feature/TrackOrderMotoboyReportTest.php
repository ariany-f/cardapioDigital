<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Delivery;
use App\Models\Motoboy;
use App\Models\Order;
use App\Models\Tenant;
use App\Support\GuestOrderAccess;
use App\Support\TenantFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackOrderMotoboyReportTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);
        $this->branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);
    }

    public function test_track_page_shows_delivery_report_when_motoboys_module_disabled(): void
    {
        TenantFeatures::setMotoboysEnabled($this->tenant, false);

        $motoboy = Motoboy::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'João',
            'phone' => '11999990000',
            'vehicle_type' => 'motorcycle',
            'employment_type' => 'freelancer',
            'is_active' => true,
            'uses_app' => false,
        ]);

        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'LOJA-REPORT-1',
            'type' => 'delivery',
            'status' => 'delivered',
            'guest_name' => 'Maria',
            'guest_phone' => '11988887777',
            'subtotal' => 50,
            'total' => 50,
        ]);

        Delivery::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'motoboy_id' => $motoboy->id,
            'status' => 'delivered',
        ]);

        $this->get("/{$this->tenant->slug}/pedido/{$order->order_number}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackOrder')
                ->where('order.can_report_motoboy', true)
                ->where('order.motoboy_name', 'João'));
    }

    public function test_guest_can_submit_delivery_report_when_motoboys_module_disabled(): void
    {
        TenantFeatures::setMotoboysEnabled($this->tenant, false);

        $motoboy = Motoboy::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'João',
            'phone' => '11999990000',
            'vehicle_type' => 'motorcycle',
            'employment_type' => 'freelancer',
            'is_active' => true,
            'uses_app' => false,
        ]);

        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'LOJA-REPORT-2',
            'type' => 'delivery',
            'status' => 'delivered',
            'guest_name' => 'Maria',
            'guest_phone' => '11988887777',
            'guest_access_code' => '482910',
            'subtotal' => 50,
            'total' => 50,
        ]);

        Delivery::create([
            'tenant_id' => $this->tenant->id,
            'order_id' => $order->id,
            'motoboy_id' => $motoboy->id,
            'status' => 'delivered',
        ]);

        $this->withSession([
            GuestOrderAccess::sessionKey($order->id) => true,
        ])
            ->post("/{$this->tenant->slug}/pedido/{$order->order_number}/denunciar-entregador", [
                'message' => 'Entrega atrasada e mal educado.',
                'guest_name' => 'Maria',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('motoboy_reports', [
            'order_id' => $order->id,
            'motoboy_id' => $motoboy->id,
            'message' => 'Entrega atrasada e mal educado.',
        ]);
    }
}
