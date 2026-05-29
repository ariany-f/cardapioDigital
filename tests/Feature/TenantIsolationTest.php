<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_are_scoped_to_current_tenant(): void
    {
        $tenantA = Tenant::create(['name' => 'A', 'slug' => 'a', 'status' => 'active']);
        $tenantB = Tenant::create(['name' => 'B', 'slug' => 'b', 'status' => 'active']);

        $branchA = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Filial A',
            'slug' => 'main',
        ]);

        Order::withoutGlobalScopes()->create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'order_number' => 'A-0001',
            'type' => 'pickup',
            'status' => 'confirmed',
            'subtotal' => 10,
            'total' => 10,
        ]);

        TenantContext::set($tenantB);

        $this->assertEquals(0, Order::count());
    }
}
