<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Support\TenantOrderSettings;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestCheckoutSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);
        TenantContext::set($this->tenant);

        $this->branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
            'pickup_available' => true,
            'delivery_available' => false,
            'orders_status_override' => 'open',
        ]);
    }

    public function test_guest_checkout_enabled_by_default(): void
    {
        $this->assertTrue(TenantOrderSettings::guestCheckoutEnabled($this->tenant));
    }

    public function test_branch_menu_available_when_guest_checkout_disabled(): void
    {
        TenantOrderSettings::merge($this->tenant, ['guest_checkout_enabled' => false]);
        $this->tenant->refresh();

        $this->get(route('tenant.branch', ['tenant' => $this->tenant->slug, 'branch' => $this->branch->slug]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Branch')
                ->where('branch.can_order', true)
                ->where('branch.guest_checkout_enabled', false));
    }

    public function test_checkout_rejects_guest_when_disabled(): void
    {
        TenantOrderSettings::merge($this->tenant, ['guest_checkout_enabled' => false]);
        $this->tenant->refresh();

        $this->post(route('tenant.checkout', ['tenant' => $this->tenant->slug]), [
            'branch_slug' => $this->branch->slug,
            'guest_name' => 'Visitante',
            'guest_phone' => '11999998888',
            'type' => 'pickup',
            'scheduled_for' => now()->addHour()->toIso8601String(),
            'payment_method' => 'on_delivery',
            'payment_channel' => 'pix',
            'items' => [
                [
                    'product_id' => 1,
                    'name' => 'Item',
                    'quantity' => 1,
                    'unit_price' => 10,
                    'variations' => [],
                ],
            ],
        ])->assertSessionHasErrors('guest');
    }
}
