<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DeliveryConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DeliveryConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('deliveries.update');
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
        $this->admin->givePermissionTo(['deliveries.update', 'orders.accept']);
    }

    public function test_code_generated_when_order_goes_out_for_delivery(): void
    {
        $order = $this->deliveryOrder('preparing');

        $order->update(['status' => 'out_for_delivery']);
        $code = app(DeliveryConfirmationService::class)->ensureCode($order->fresh());

        $this->assertNotNull($code);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $order->fresh()->delivery_confirmation_code);
    }

    public function test_admin_confirms_delivery_with_valid_code(): void
    {
        $order = $this->deliveryOrder('out_for_delivery');
        $order->update(['delivery_confirmation_code' => '482910']);

        $this->actingAs($this->admin)
            ->post("/{$this->tenant->slug}/admin/orders/{$order->id}/confirm-delivery", [
                'confirmation_code' => '482910',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertNotNull($order->delivery_confirmed_at);
    }

    public function test_wrong_code_is_rejected(): void
    {
        $order = $this->deliveryOrder('out_for_delivery');
        $order->update(['delivery_confirmation_code' => '482910']);

        $this->actingAs($this->admin)
            ->post("/{$this->tenant->slug}/admin/orders/{$order->id}/confirm-delivery", [
                'confirmation_code' => '000000',
            ])
            ->assertSessionHasErrors('confirmation_code');

        $this->assertSame('out_for_delivery', $order->fresh()->status);
    }

    public function test_track_page_includes_code_for_customer(): void
    {
        $order = $this->deliveryOrder('out_for_delivery');
        $order->update(['delivery_confirmation_code' => '123456']);

        $this->get("/{$this->tenant->slug}/pedido/{$order->order_number}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackOrder')
                ->where('order.show_delivery_code', true)
                ->where('order.delivery_confirmation_code', '123456'));
    }

    public function test_track_page_generates_code_when_missing_for_out_for_delivery(): void
    {
        $order = $this->deliveryOrder('out_for_delivery');
        $this->assertNull($order->delivery_confirmation_code);

        $this->get("/{$this->tenant->slug}/pedido/{$order->order_number}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackOrder')
                ->where('order.show_delivery_code', true)
                ->where('order.delivery_confirmation_code', fn ($code) => is_string($code) && strlen($code) === 6));

        $this->assertMatchesRegularExpression(
            '/^\d{6}$/',
            $order->fresh()->delivery_confirmation_code,
        );
    }

    public function test_admin_status_update_generates_delivery_code(): void
    {
        $order = $this->deliveryOrder('ready');

        $this->actingAs($this->admin)
            ->patch("/{$this->tenant->slug}/admin/orders/{$order->id}/status", [
                'status' => 'out_for_delivery',
            ])
            ->assertRedirect();

        $this->assertMatchesRegularExpression(
            '/^\d{6}$/',
            $order->fresh()->delivery_confirmation_code,
        );
    }

    public function test_track_page_shows_delivery_details(): void
    {
        $order = $this->deliveryOrder('preparing');
        $order->update([
            'subtotal' => 28.90,
            'delivery_fee' => 8.00,
            'packaging_fee' => 3.90,
            'total' => 40.80,
            'payment_method' => 'on_delivery',
            'payment_channel' => 'pix',
            'payment_status' => 'paid',
            'delivery_address' => [
                'street' => 'Rua das Flores',
                'number' => '100',
                'neighborhood' => 'Centro',
                'city' => 'São Paulo',
                'state' => 'SP',
                'postal_code' => '01000-000',
            ],
        ]);

        $this->get("/{$this->tenant->slug}/pedido/{$order->order_number}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/TrackOrder')
                ->where('order.delivery_fee', '8.00')
                ->where('order.payment_status_label', 'Pago')
                ->where('order.delivery_address_formatted', fn ($v) => str_contains($v, 'Rua das Flores')));
    }

    protected function deliveryOrder(string $status): Order
    {
        return Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'ACME-'.random_int(1000, 9999),
            'type' => 'delivery',
            'status' => $status,
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'subtotal' => 50,
            'total' => 50,
        ]);
    }
}
