<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AppliesAdminListSearch;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DeliveryZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryZoneController extends Controller
{
    use AppliesAdminListSearch;

    public function index(Request $request, string $tenant, Branch $branch): Response
    {
        $term = $this->listSearchTerm($request);
        $query = DeliveryZone::query()
            ->where('branch_id', $branch->id)
            ->orderBy('name');

        if ($term !== null) {
            $this->applyListSearch($query, $term, ['name']);
        }

        return Inertia::render('Admin/DeliveryZones/Index', [
            'branch' => $branch->only('id', 'name', 'slug'),
            'zones' => $query
                ->get()
                ->map(fn ($z) => [
                    'id' => $z->id,
                    'name' => $z->name,
                    'type' => $z->type,
                    'rules' => $z->rules,
                    'fee_per_km' => $z->rules['fee_per_km'] ?? null,
                    'delivery_fee' => $z->delivery_fee,
                    'min_order_override' => $z->min_order_override,
                    'is_active' => $z->is_active,
                ]),
            'filters' => $this->listSearchFilters($request),
        ]);
    }

    public function store(Request $request, string $tenant, Branch $branch): RedirectResponse
    {
        $data = $this->validated($request);

        DeliveryZone::create([
            'tenant_id' => $branch->tenant_id,
            'branch_id' => $branch->id,
            ...$data,
        ]);

        return back()->with('success', 'Zona de entrega criada.');
    }

    public function update(Request $request, string $tenant, Branch $branch, DeliveryZone $zone): RedirectResponse
    {
        $this->ensureZoneBelongsToBranch($branch, $zone);

        $data = $this->validated($request);
        $zone->update($data);

        return back()->with('success', 'Zona atualizada.');
    }

    public function destroy(Branch $branch, DeliveryZone $zone): RedirectResponse
    {
        $zone->delete();

        return back()->with('success', 'Zona removida.');
    }

    protected function ensureZoneBelongsToBranch(Branch $branch, DeliveryZone $zone): void
    {
        if ($zone->branch_id !== $branch->id) {
            abort(404);
        }
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:flat,neighborhood,per_km'],
            'delivery_fee' => [
                Rule::requiredIf(fn () => $request->input('type') !== 'per_km'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'fee_per_km' => [
                Rule::requiredIf(fn () => $request->input('type') === 'per_km'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'min_order_override' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'neighborhoods_text' => ['nullable', 'string'],
        ]);

        if ($data['type'] === 'per_km') {
            $data['delivery_fee'] = $data['delivery_fee'] ?? 0;
        }

        $rules = [];
        if ($data['type'] === 'neighborhood') {
            $lines = preg_split('/[\r\n,;]+/', $data['neighborhoods_text'] ?? '');
            $rules['neighborhoods'] = array_values(array_filter(array_map('trim', $lines)));
        }
        if ($data['type'] === 'per_km') {
            $rules['fee_per_km'] = (float) $data['fee_per_km'];
        }

        return [
            'name' => $data['name'],
            'type' => $data['type'],
            'rules' => $rules,
            'delivery_fee' => $data['delivery_fee'],
            'min_order_override' => $data['min_order_override'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ];
    }
}
