<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBranchOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_page_is_single_public_url(): void
    {
        $tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
            'opening_hours' => ['mon' => ['08:00', '23:00']],
        ]);

        $this->get('/acme/centro')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Branch')
                ->has('categories')
                ->where('branch.slug', 'centro'));

        $this->get('/acme/centro/cardapio')
            ->assertRedirect('/acme/centro');
    }

    public function test_checkout_rejected_when_branch_closed(): void
    {
        $tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
            'opening_hours' => ['mon' => ['23:59', '23:59']],
        ]);

        $this->post('/acme/checkout', [
            'branch_slug' => 'centro',
            'guest_name' => 'Cliente',
            'guest_phone' => '11999999999',
            'type' => 'pickup',
            'payment_method' => 'on_delivery',
            'items' => [
                ['product_id' => 1, 'name' => 'Item', 'quantity' => 1, 'unit_price' => 10],
            ],
        ])->assertSessionHasErrors('branch');
    }
}
