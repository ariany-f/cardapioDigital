<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AppliesAdminListSearch;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DiningTable;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DiningTableController extends Controller
{
    use AppliesAdminListSearch;

    public function index(Request $request): Response
    {
        $tenantSlug = $request->route('tenant');
        $term = $this->listSearchTerm($request);

        $query = DiningTable::query()
            ->with('branch:id,name,slug')
            ->orderBy('branch_id')
            ->orderBy('name');

        if ($term !== null) {
            $this->applyListSearch($query, $term, [
                'name',
                fn ($inner, $t, $like) => $inner->orWhereHas('branch', fn ($b) => $b->where('name', 'like', $like)),
            ]);
        }

        $tables = $query
            ->get()
            ->map(fn (DiningTable $table) => [
                'id' => $table->id,
                'name' => $table->name,
                'qr_token' => $table->qr_token,
                'branch' => $table->branch?->only(['id', 'name', 'slug']),
                'menu_url' => $table->branch
                    ? url("/{$tenantSlug}/{$table->branch->slug}/mesa/{$table->qr_token}")
                    : null,
            ]);

        return Inertia::render('Admin/Tables/Index', [
            'tables' => $tables,
            'branches' => Branch::orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => $this->listSearchFilters($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $token = Str::lower(Str::random(12));

        DiningTable::create([
            'tenant_id' => TenantContext::id(),
            'branch_id' => $data['branch_id'],
            'name' => $data['name'],
            'qr_token' => $token,
        ]);

        return back()->with('success', 'Mesa cadastrada. Copie o link do QR na lista.');
    }

    public function destroy(string $tenant, DiningTable $table): RedirectResponse
    {
        $table->delete();

        return back()->with('success', 'Mesa removida.');
    }
}
