<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AppliesAdminListSearch;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    use AppliesAdminListSearch;

    public function index(Request $request): Response
    {
        $term = $this->listSearchTerm($request);
        $query = Category::query()->orderBy('sort_order')->orderBy('name');

        if ($term !== null) {
            $this->applyListSearch($query, $term, ['name']);
        }

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $query->get(),
            'filters' => $this->listSearchFilters($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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

    public function update(Request $request, string $tenant, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_paused' => ['boolean'],
        ]);

        $category->update($data);

        return back()->with('success', 'Categoria atualizada.');
    }

    public function destroy(string $tenant, Category $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', 'Categoria removida.');
    }
}
