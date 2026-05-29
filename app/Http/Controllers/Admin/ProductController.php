<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesProductImageUpload;
use App\Http\Controllers\Concerns\LogsCrudActivity;
use App\Http\Controllers\Concerns\ManagesProductVariations;
use App\Http\Controllers\Concerns\ValidatesProductCatalog;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    use HandlesProductImageUpload;
    use LogsCrudActivity;
    use ManagesProductVariations;
    use ValidatesProductCatalog;

    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Products/Index', [
            'products' => $this->productsListPayload($request),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'filters' => $this->listSearchFilters($request),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->productsPage($request, ['creating' => true]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->productFieldsRules(true));

        $product = Product::create($this->productAttributes($data));

        $product->branches()->sync($data['branch_ids'] ?? Branch::pluck('id'));
        $this->syncProductVariations($product, $data['variation_groups'] ?? []);
        $this->storeProductImage($request, $product);
        $this->logCrud($product, 'product.created', 'Produto criado', ['name' => $product->name]);

        return redirect()->route('tenant.admin.products.index', ['tenant' => request()->route('tenant')])
            ->with('success', 'Produto criado.');
    }

    public function edit(Request $request, string $tenant, Product $product): Response
    {
        return $this->productsPage($request, [
            'editingProduct' => $this->productWithVariations($product),
        ]);
    }

    public function update(Request $request, string $tenant, Product $product): RedirectResponse
    {
        $data = $request->validate($this->productFieldsRules(true));

        $product->update($this->productAttributes($data));

        $product->branches()->sync($data['branch_ids'] ?? []);
        $this->syncProductVariations($product, $data['variation_groups'] ?? null);
        $this->storeProductImage($request, $product);
        $this->logCrud($product, 'product.updated', 'Produto atualizado', ['name' => $product->name]);

        return redirect()->route('tenant.admin.products.index', ['tenant' => $tenant])
            ->with('success', 'Produto atualizado.');
    }

    protected function productsPage(Request $request, array $extra = []): Response
    {
        return Inertia::render('Admin/Products/Index', [
            'products' => $this->productsListPayload($request),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'filters' => $this->listSearchFilters($request),
            ...$extra,
        ]);
    }

    public function destroy(string $tenant, Product $product): RedirectResponse
    {
        $this->logCrud($product, 'product.deleted', 'Produto removido', ['name' => $product->name]);
        $product->delete();

        return redirect()->route('tenant.admin.products.index', ['tenant' => $tenant])
            ->with('success', 'Produto removido.');
    }
}
