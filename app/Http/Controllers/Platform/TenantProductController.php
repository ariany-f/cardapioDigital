<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Concerns\HandlesProductImageUpload;
use App\Http\Controllers\Concerns\ManagesProductVariations;
use App\Http\Controllers\Concerns\ValidatesProductCatalog;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\ManagesTenantCatalog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantProductController extends Controller
{
    use HandlesProductImageUpload;
    use ManagesProductVariations;
    use ManagesTenantCatalog;
    use ValidatesProductCatalog;

    public function index(Tenant $tenant): Response
    {
        $this->bindTenant($tenant);

        return $this->productsPage($tenant);
    }

    public function create(Tenant $tenant): Response
    {
        $this->bindTenant($tenant);

        return $this->productsPage($tenant, ['creating' => true]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->bindTenant($tenant);

        $data = $request->validate($this->productFieldsRules(true));

        $product = Product::create($this->productAttributes($data));

        $this->syncProductBranches($product, $tenant, $data['branch_ids'] ?? []);
        $this->syncProductVariations($product, $data['variation_groups'] ?? []);
        $this->storeProductImage($request, $product);

        return redirect()
            ->route('platform.tenants.products.index', $tenant)
            ->with('success', 'Produto criado.');
    }

    public function edit(Tenant $tenant, Product $product): Response
    {
        $this->bindTenant($tenant);
        $product = $this->findProduct($tenant, $product->id);

        return $this->productsPage($tenant, [
            'editingProduct' => $this->productWithVariations($product),
        ]);
    }

    public function update(Request $request, Tenant $tenant, Product $product): RedirectResponse
    {
        $this->bindTenant($tenant);
        $product = $this->findProduct($tenant, $product->id);

        $data = $request->validate($this->productFieldsRules(true));

        $product->update($this->productAttributes($data));

        $this->syncProductBranches($product, $tenant, $data['branch_ids'] ?? []);
        $this->syncProductVariations($product, $data['variation_groups'] ?? null);
        $this->storeProductImage($request, $product);

        return redirect()
            ->route('platform.tenants.products.index', $tenant)
            ->with('success', 'Produto atualizado.');
    }

    public function destroy(Tenant $tenant, Product $product): RedirectResponse
    {
        $this->bindTenant($tenant);
        $this->findProduct($tenant, $product->id)->delete();

        return redirect()
            ->route('platform.tenants.products.index', $tenant)
            ->with('success', 'Produto removido.');
    }

    protected function productsPage(Tenant $tenant, array $extra = []): Response
    {
        return Inertia::render('Platform/Tenants/Catalog/Products', [
            'platformTenant' => $this->tenantPayload($tenant),
            'products' => $this->productsListPayload(),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'branches' => $this->branchesFor($tenant),
            ...$extra,
        ]);
    }
}
