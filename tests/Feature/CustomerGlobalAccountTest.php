<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerGlobalAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_login_from_any_tenant_and_see_all_orders(): void
    {
        $tenantA = Tenant::create(['name' => 'Restaurante A', 'slug' => 'rest-a', 'status' => 'active']);
        $tenantB = Tenant::create(['name' => 'Restaurante B', 'slug' => 'rest-b', 'status' => 'active']);

        $branchA = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Filial A',
            'slug' => 'main',
        ]);

        $branchB = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Filial B',
            'slug' => 'main',
        ]);

        $customer = Customer::create([
            'name' => 'Cliente Global',
            'email' => 'global@test.test',
            'phone' => '11999990000',
            'password' => Hash::make('secret'),
        ]);

        Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'customer_id' => $customer->id,
            'order_number' => 'A-0001',
            'type' => 'pickup',
            'status' => 'confirmed',
            'subtotal' => 10,
            'total' => 10,
        ]);

        Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'branch_id' => $branchB->id,
            'customer_id' => $customer->id,
            'order_number' => 'B-0001',
            'type' => 'pickup',
            'status' => 'confirmed',
            'subtotal' => 20,
            'total' => 20,
        ]);

        $this->post('/rest-b/conta/login', [
            'email' => 'global@test.test',
            'password' => 'secret',
        ])->assertRedirect(route('tenant.conta.dashboard', ['tenant' => 'rest-b']));

        $this->actingAs($customer, 'customer')
            ->get('/rest-b/conta')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Conta/Dashboard')
                ->has('orders', 2)
            );
    }

    public function test_global_conta_route_lists_orders(): void
    {
        $tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
        ]);

        $customer = Customer::create([
            'name' => 'Cliente',
            'email' => 'cliente@demo.test',
            'phone' => '11999999999',
            'password' => Hash::make('password'),
        ]);

        Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'order_number' => 'ACME-0001',
            'type' => 'pickup',
            'status' => 'confirmed',
            'subtotal' => 15,
            'total' => 15,
        ]);

        $this->actingAs($customer, 'customer')
            ->get('/conta')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Conta/Dashboard')
                ->where('globalAccount', true)
                ->has('orders', 1)
                ->where('orders.0.order_number', 'ACME-0001')
            );
    }
}
