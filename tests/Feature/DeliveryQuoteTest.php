<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\DeliveryZone;
use App\Models\Tenant;
use App\Services\DeliveryQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_per_km_zone_charges_base_plus_distance_times_rate(): void
    {
        $tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
            'latitude' => -23.5505,
            'longitude' => -46.6333,
            'delivery_radius_km' => 15,
        ]);

        DeliveryZone::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Por distância',
            'type' => 'per_km',
            'rules' => ['fee_per_km' => 2.5],
            'delivery_fee' => 5,
            'is_active' => true,
        ]);

        $quote = app(DeliveryQuoteService::class)->quote(
            $branch,
            'delivery',
            address: ['neighborhood' => 'Centro'],
            customerLat: -23.5610,
            customerLng: -46.6400,
        );

        $this->assertTrue($quote['allowed']);
        $this->assertNotNull($quote['distance_km']);
        $expected = round(5 + ($quote['distance_km'] * 2.5), 2);
        $this->assertSame($expected, $quote['fee']);
    }

    public function test_flat_zone_ignores_distance_for_fee(): void
    {
        $tenant = Tenant::create(['name' => 'ACME', 'slug' => 'acme', 'status' => 'active']);
        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
            'latitude' => -23.5505,
            'longitude' => -46.6333,
        ]);

        DeliveryZone::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'Fixa',
            'type' => 'flat',
            'rules' => [],
            'delivery_fee' => 8.90,
            'is_active' => true,
        ]);

        $quote = app(DeliveryQuoteService::class)->quote(
            $branch,
            'delivery',
            customerLat: -23.5610,
            customerLng: -46.6400,
        );

        $this->assertSame(8.90, $quote['fee']);
        $this->assertNull($quote['distance_km']);
    }
}
