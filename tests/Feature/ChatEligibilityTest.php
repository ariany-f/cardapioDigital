<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Tenant;
use App\Support\ChatEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ChatEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_needs_purchase_session(): void
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);

        $session = $this->app->make('session.store');
        $request = Request::create('/');
        $request->setLaravelSession($session);

        $this->assertFalse(ChatEligibility::canStart($branch, null, $request));

        $session->put(
            ChatEligibility::purchasedSessionKey($tenant->id, $branch->id),
            now()->toIso8601String(),
        );
        $this->assertTrue(ChatEligibility::canStart($branch, null, $request));

        $session->put(
            ChatEligibility::purchasedSessionKey($tenant->id, $branch->id),
            now()->subDays(16)->toIso8601String(),
        );
        $this->assertFalse(ChatEligibility::canStart($branch, null, $request));
    }

    public function test_customer_needs_at_least_one_order(): void
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja2', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Cliente',
            'email' => 'c@test.com',
            'phone' => '11988887777',
            'password' => 'password',
        ]);

        $this->assertFalse(ChatEligibility::canStart($branch, $customer, request()));

        Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'order_number' => 'LOJA-0001',
            'status' => 'delivered',
            'type' => 'pickup',
            'source' => 'web',
            'subtotal' => 10,
            'total' => 10,
        ]);

        $this->assertTrue(ChatEligibility::canStart($branch, $customer, request()));

        $oldOrder = Order::withoutGlobalScopes()->where('customer_id', $customer->id)->first();
        $oldOrder->created_at = now()->subDays(20);
        $oldOrder->saveQuietly();

        $this->assertFalse(ChatEligibility::canStart($branch, $customer, request()));
    }

    public function test_track_order_does_not_refresh_chat_for_old_guest_order(): void
    {
        $this->flushSession();

        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja-old', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
        ]);

        $order = Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'order_number' => 'LOJA-OLD-1',
            'status' => 'delivered',
            'type' => 'pickup',
            'source' => 'web',
            'guest_name' => 'Visitante',
            'guest_phone' => '11999998888',
            'subtotal' => 10,
            'total' => 10,
        ]);
        $order->created_at = now()->subDays(20);
        $order->saveQuietly();

        $this->get(route('tenant.track', ['tenant' => $tenant->slug, 'order_number' => $order->order_number]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('chatAvailable', false));
    }
}
