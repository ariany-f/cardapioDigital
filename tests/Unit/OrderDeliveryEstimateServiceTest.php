<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\OrderDeliveryEstimateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDeliveryEstimateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function branchWithTimes(int $prepDefault = 30, int $delivery = 20): Branch
    {
        $tenant = Tenant::create(['name' => 'Loja', 'slug' => 'loja', 'status' => 'active']);

        return Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Centro',
            'slug' => 'centro',
            'is_active' => true,
            'default_prep_time_minutes' => $prepDefault,
            'delivery_time_minutes' => $delivery,
        ]);
    }

    protected function productFor(Branch $branch, ?int $prepMinutes): Product
    {
        $category = Category::withoutGlobalScopes()->create([
            'tenant_id' => $branch->tenant_id,
            'name' => 'Pratos',
        ]);

        $product = Product::withoutGlobalScopes()->create([
            'tenant_id' => $branch->tenant_id,
            'category_id' => $category->id,
            'name' => 'Item',
            'base_price' => 10,
            'prep_time_minutes' => $prepMinutes,
        ]);
        $product->branches()->attach($branch->id, [
            'tenant_id' => $branch->tenant_id,
            'is_available' => true,
        ]);

        return $product;
    }

    public function test_uses_default_when_no_product_prep_times(): void
    {
        $branch = $this->branchWithTimes(30, 20);
        $service = new OrderDeliveryEstimateService;

        $this->assertSame(30, $service->estimatePrepMinutes($branch, [['combo_id' => 1]]));
        $this->assertSame(50, $service->estimateDeliveryMinutes($branch, [['combo_id' => 1]]));
    }

    public function test_uses_max_product_prep_time(): void
    {
        $branch = $this->branchWithTimes(30, 15);
        $fast = $this->productFor($branch, 20);
        $slow = $this->productFor($branch, 45);

        $service = new OrderDeliveryEstimateService;

        $this->assertSame(45, $service->estimatePrepMinutes($branch, [
            ['product_id' => $fast->id],
            ['product_id' => $slow->id],
        ]));
        $this->assertSame(60, $service->estimateDeliveryMinutes($branch, [
            ['product_id' => $slow->id],
        ]));
    }

    public function test_pickup_uses_prep_only(): void
    {
        $branch = $this->branchWithTimes(25, 40);
        $service = new OrderDeliveryEstimateService;

        $this->assertSame(25, $service->estimateTotalMinutes($branch, 'pickup', []));
    }
}
