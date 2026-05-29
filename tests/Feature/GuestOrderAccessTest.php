<?php

namespace Tests\Feature;

use App\Mail\GuestOrderAccessMail;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Support\GuestOrderAccess;
use App\Support\TenantContext;
use App\Support\TenantOrderSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuestOrderAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);
        TenantContext::set($this->tenant);

        $this->branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
            'pickup_available' => true,
            'orders_status_override' => 'open',
        ]);

        $category = Category::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Geral',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->product = Product::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'name' => 'Item teste',
            'base_price' => 10,
            'is_active' => true,
            'is_paused' => false,
        ]);

        $this->product->branches()->attach($this->branch->id, [
            'tenant_id' => $this->tenant->id,
            'is_available' => true,
        ]);
    }

    protected function guestOrder(array $overrides = []): Order
    {
        return Order::withoutGlobalScopes()->create(array_merge([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'order_number' => 'LOJA-0099',
            'status' => 'confirmed',
            'type' => 'pickup',
            'source' => 'web',
            'subtotal' => 20,
            'total' => 20,
            'guest_name' => 'Maria',
            'guest_phone' => '11999998888',
            'guest_email' => 'maria@test.com',
            'guest_access_code' => '482910',
        ], $overrides));
    }

    public function test_track_requires_code_for_guest_orders(): void
    {
        $this->guestOrder();

        $this->get(route('tenant.track', [
            'tenant' => $this->tenant->slug,
            'order_number' => 'LOJA-0099',
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/TrackOrderAccess'));
    }

    public function test_track_granted_with_query_code(): void
    {
        $this->guestOrder();

        $this->get(route('tenant.track', [
            'tenant' => $this->tenant->slug,
            'order_number' => 'LOJA-0099',
            'code' => '482910',
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/TrackOrder'));
    }

    public function test_lookup_with_phone_and_code(): void
    {
        $order = $this->guestOrder();

        $this->post(route('tenant.track.lookup.store', ['tenant' => $this->tenant->slug]), [
            'code' => '482910',
            'phone' => '(11) 99999-8888',
        ])
            ->assertRedirect(GuestOrderAccess::trackUrl($order, $this->tenant, false));

        $this->get(route('tenant.track', [
            'tenant' => $this->tenant->slug,
            'order_number' => 'LOJA-0099',
        ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/TrackOrder'));
    }

    public function test_lookup_rejects_wrong_code(): void
    {
        $this->guestOrder();

        $this->post(route('tenant.track.lookup.store', ['tenant' => $this->tenant->slug]), [
            'code' => '000000',
            'phone' => '11999998888',
        ])->assertSessionHasErrors('code');
    }

    public function test_lookup_page_hidden_when_guest_checkout_disabled(): void
    {
        TenantOrderSettings::merge($this->tenant, ['guest_checkout_enabled' => false]);
        $this->tenant->refresh();

        $this->get(route('tenant.track.lookup', ['tenant' => $this->tenant->slug]))
            ->assertNotFound();
    }

    public function test_checkout_sends_access_email_when_guest_has_email(): void
    {
        Mail::fake();

        $response = $this->post(route('tenant.checkout', ['tenant' => $this->tenant->slug]), [
            'branch_slug' => $this->branch->slug,
            'guest_name' => 'Visitante',
            'guest_phone' => '11988887777',
            'guest_email' => 'visitante@test.com',
            'type' => 'pickup',
            'payment_method' => 'on_delivery',
            'payment_channel' => 'pix',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'name' => 'Item teste',
                    'quantity' => 1,
                    'unit_price' => 10,
                    'variations' => [],
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('guest_access_code');

        Mail::assertSent(GuestOrderAccessMail::class, fn ($mail) => $mail->hasTo('visitante@test.com'));

        $order = Order::withoutGlobalScopes()->latest('id')->first();
        $this->assertNotNull($order->guest_access_code);
        $this->assertSame(6, strlen($order->guest_access_code));
    }
}
