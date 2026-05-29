<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Motoboy;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DeliveryStatusService;
use App\Support\TenantContext;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotoboyAppTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected Motoboy $motoboy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        TenantContext::set($this->tenant);
        $this->branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);
        $this->motoboy = Motoboy::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'João',
            'phone' => '11988887777',
            'email' => 'motoboy@acme.test',
            'password' => 'password',
            'vehicle_type' => 'motorcycle',
            'employment_type' => 'freelancer',
            'operational_status' => 'available',
            'max_active_deliveries' => 3,
            'is_active' => true,
            'uses_app' => true,
            'access_all_branches' => true,
        ]);
    }

    public function test_motoboy_can_login_and_see_dashboard(): void
    {
        $this->post("/{$this->tenant->slug}/entregador/login", [
            'email' => 'motoboy@acme.test',
            'password' => 'password',
        ])->assertRedirect(route('tenant.entregador.dashboard', ['tenant' => 'acme']));

        $this->assertAuthenticatedAs($this->motoboy, 'motoboy');

        $this->get("/{$this->tenant->slug}/entregador")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Entregador/Dashboard'));
    }

    public function test_motoboy_can_accept_assignment_and_deliver_with_code(): void
    {
        $order = $this->deliveryOrder();
        $delivery = app(DeliveryStatusService::class)->assignMotoboy($order, $this->motoboy->id);

        $this->assertSame('pending', $delivery->motoboy_assignment_status);

        $this->actingAs($this->motoboy, 'motoboy')
            ->post("/{$this->tenant->slug}/entregador/entregas/{$delivery->id}/respond", [
                'accept' => true,
            ])
            ->assertRedirect();

        $delivery->refresh();
        $this->assertSame('accepted', $delivery->motoboy_assignment_status);

        $this->actingAs($this->motoboy, 'motoboy')
            ->patch("/{$this->tenant->slug}/entregador/entregas/{$delivery->id}/status", [
                'delivery_status' => 'on_route',
            ])
            ->assertRedirect();

        $code = $order->fresh()->delivery_confirmation_code;
        $this->assertNotNull($code);

        $this->actingAs($this->motoboy, 'motoboy')
            ->patch("/{$this->tenant->slug}/entregador/entregas/{$delivery->id}/status", [
                'delivery_status' => 'delivered',
                'confirmation_code' => $code,
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertNotNull($order->delivery_confirmed_at);
    }

    public function test_cannot_assign_motoboy_to_branch_they_do_not_serve(): void
    {
        $otherBranch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Zona Sul',
            'slug' => 'zona-sul',
            'is_active' => true,
        ]);

        $restricted = Motoboy::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Só Centro',
            'phone' => '11933332222',
            'vehicle_type' => 'motorcycle',
            'employment_type' => 'freelancer',
            'operational_status' => 'available',
            'max_active_deliveries' => 2,
            'is_active' => true,
            'uses_app' => false,
        ]);
        $restricted->update(['access_all_branches' => false]);
        $restricted->branches()->sync([$this->branch->id]);
        $restricted->refresh();

        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $otherBranch->id,
            'order_number' => 'ACME-OTHER',
            'type' => 'delivery',
            'status' => 'preparing',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 50,
            'total' => 50,
        ]);

        $restricted->refresh();

        $this->assertNotSame($this->branch->id, $otherBranch->id);
        $this->assertFalse($restricted->access_all_branches);
        $this->assertFalse(\App\Support\MotoboyBranchAccess::canServeBranch($restricted, $otherBranch->id));

        try {
            app(DeliveryStatusService::class)->assignMotoboy($order, $restricted->id);
            $this->fail('Atribuição deveria falhar para filial não atendida.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public function test_manual_motoboy_assignment_does_not_wait_for_app(): void
    {
        $this->seed(PlatformSeeder::class);

        $manual = Motoboy::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Parceiro impresso',
            'phone' => '11955554444',
            'vehicle_type' => 'motorcycle',
            'employment_type' => 'partner',
            'operational_status' => 'available',
            'max_active_deliveries' => 1,
            'is_active' => true,
            'uses_app' => false,
            'access_all_branches' => false,
        ]);
        $manual->branches()->sync([$this->branch->id]);

        $order = $this->deliveryOrder();
        $delivery = app(DeliveryStatusService::class)->assignMotoboy($order, $manual->id);

        $this->assertSame('accepted', $delivery->motoboy_assignment_status);

        $admin = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_platform_user' => false,
        ]);
        $admin->assignRole('tenant_admin');

        $this->actingAs($admin)
            ->patch(route('tenant.admin.orders.delivery', [
                'tenant' => $this->tenant->slug,
                'order' => $order->id,
            ]), [
                'motoboy_id' => $manual->id,
                'delivery_status' => 'on_route',
            ])
            ->assertRedirect();
    }

    public function test_manual_motoboy_cannot_login_to_app(): void
    {
        Motoboy::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sem app',
            'phone' => '11944443333',
            'email' => 'semapp@acme.test',
            'password' => 'password',
            'vehicle_type' => 'motorcycle',
            'employment_type' => 'partner',
            'operational_status' => 'available',
            'max_active_deliveries' => 1,
            'is_active' => true,
            'uses_app' => false,
        ]);

        $this->post("/{$this->tenant->slug}/entregador/login", [
            'email' => 'semapp@acme.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_auto_accept_assignment_skips_pending(): void
    {
        $this->tenant->update([
            'settings_json' => ['motoboy_auto_accept_assignments' => true],
        ]);

        $order = $this->deliveryOrder();
        $delivery = app(DeliveryStatusService::class)->assignMotoboy($order, $this->motoboy->id);

        $this->assertSame('accepted', $delivery->fresh()->motoboy_assignment_status);
    }

    protected function deliveryOrder(): Order
    {
        return Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'ACME-'.random_int(1000, 9999),
            'type' => 'delivery',
            'status' => 'preparing',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 50,
            'total' => 50,
        ]);
    }
}
