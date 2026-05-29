<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Concerns\AppliesAdminListSearch;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\ManagesTenantCatalog;
use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantCategoryController extends Controller
{
    use AppliesAdminListSearch;
    use ManagesTenantCatalog;

    public function index(Request $request, Tenant $tenant): Response
    {
        $this->bindTenant($tenant);

        $term = $this->listSearchTerm($request);
        $query = Category::query()->orderBy('sort_order')->orderBy('name');

        if ($term !== null) {
            $this->applyListSearch($query, $term, ['name']);
        }

        return Inertia::render('Platform/Tenants/Catalog/Categories', [
            'platformTenant' => $this->tenantPayload($tenant),
            'categories' => $query->get(),
            'filters' => $this->listSearchFilters($request),
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->bindTenant($tenant);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        Category::create([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return back()->with('success', 'Categoria criada.');
    }

    public function update(Request $request, Tenant $tenant, Category $category): RedirectResponse
    {
        $this->bindTenant($tenant);
        $category = $this->findCategory($tenant, $category->id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_paused' => ['boolean'],
        ]);

        $category->update($data);

        return back()->with('success', 'Categoria atualizada.');
    }

    public function destroy(Tenant $tenant, Category $category): RedirectResponse
    {
        $this->bindTenant($tenant);
        $this->findCategory($tenant, $category->id)->delete();

        return back()->with('success', 'Categoria removida.');
    }
}
