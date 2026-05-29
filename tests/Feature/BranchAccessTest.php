<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $admin;

    protected Branch $branchA;

    protected Branch $branchB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlatformSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->tenant = Tenant::where('slug', 'acme')->first();
        $this->admin = User::where('email', 'admin@acme.test')->first();
        $this->branchA = Branch::where('slug', 'centro')->first();
        $this->branchB = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Zona Sul',
            'slug' => 'sul',
            'is_active' => true,
        ]);
    }

    public function test_branch_staff_only_sees_allowed_branch_orders(): void
    {
        $staff = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'staff@acme.test',
            'access_all_branches' => false,
        ]);
        $staff->assignRole('branch_staff');
        $staff->branches()->sync([$this->branchA->id]);

        Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branchA->id,
            'order_number' => 'ACME-S1',
            'type' => 'pickup',
            'status' => 'confirmed',
            'subtotal' => 10,
            'total' => 10,
            'guest_name' => 'A',
            'guest_phone' => '1',
        ]);

        Order::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branchB->id,
            'order_number' => 'ACME-S2',
            'type' => 'pickup',
            'status' => 'confirmed',
            'subtotal' => 20,
            'total' => 20,
            'guest_name' => 'B',
            'guest_phone' => '2',
        ]);

        $response = $this->actingAs($staff)
            ->get(route('tenant.admin.orders.index', ['tenant' => $this->tenant->slug]))
            ->assertOk();

        $numbers = collect($response->original->getData()['page']['props']['orders']['data'] ?? [])
            ->pluck('order_number');

        $this->assertTrue($numbers->contains('ACME-S1'));
        $this->assertFalse($numbers->contains('ACME-S2'));
    }
}
