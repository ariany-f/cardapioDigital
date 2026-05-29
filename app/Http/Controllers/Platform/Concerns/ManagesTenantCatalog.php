<?php

namespace App\Http\Controllers\Platform\Concerns;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Support\TenantContext;

trait ManagesTenantCatalog
{
    protected function bindTenant(Tenant $tenant): Tenant
    {
        TenantContext::set($tenant);

        return $tenant;
    }

    protected function tenantPayload(Tenant $tenant): array
    {
        return $tenant->only(['id', 'name', 'slug', 'status']);
    }

    protected function findProduct(Tenant $tenant, int $productId): Product
    {
        return Product::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($productId)
            ->firstOrFail();
    }

    protected function findCategory(Tenant $tenant, int $categoryId): Category
    {
        return Category::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($categoryId)
            ->firstOrFail();
    }

    protected function branchesFor(Tenant $tenant)
    {
        return Branch::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    protected function syncProductBranches(Product $product, Tenant $tenant, array $branchIds): void
    {
        $ids = $branchIds ?: $this->branchesFor($tenant)->pluck('id')->all();
        $sync = [];
        foreach ($ids as $branchId) {
            $sync[$branchId] = ['tenant_id' => $tenant->id, 'is_available' => true];
        }
        $product->branches()->sync($sync);
    }
}
