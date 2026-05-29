<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Product;
use App\Support\MediaUrl;
use App\Support\ProductImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComboController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Combos/Index', [
            'combos' => Combo::query()
                ->with(['branch:id,name', 'items.product:id,name'])
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'price' => $c->price,
                    'branch_id' => $c->branch_id,
                    'branch_name' => $c->branch?->name ?? 'Todas',
                    'is_active' => $c->is_active,
                    'image_url' => MediaUrl::fromPath($c->image_path),
                    'items' => $c->items->map(fn ($i) => [
                        'product_id' => $i->product_id,
                        'product_name' => $i->product?->name,
                        'quantity' => $i->quantity,
                    ]),
                ]),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $combo = Combo::create([
            'branch_id' => $data['branch_id'] ?: null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->syncItems($combo, $data['items']);
        $this->storeImage($request, $combo);

        return back()->with('success', 'Combo criado.');
    }

    public function update(Request $request, Combo $combo): RedirectResponse
    {
        $data = $this->validated($request);

        $combo->update([
            'branch_id' => $data['branch_id'] ?: null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'is_active' => $data['is_active'] ?? false,
        ]);

        $combo->items()->delete();
        $this->syncItems($combo, $data['items']);
        $this->storeImage($request, $combo);

        return back()->with('success', 'Combo atualizado.');
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        ProductImageStorage::delete($combo->image_path);
        $combo->delete();

        return back()->with('success', 'Combo removido.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_active' => ['boolean'],
            ...\App\Support\SecureImageUpload::rules('image'),
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
    }

    protected function syncItems(Combo $combo, array $items): void
    {
        foreach ($items as $item) {
            ComboItem::create([
                'combo_id' => $combo->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }
    }

    protected function storeImage(Request $request, Combo $combo): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        ProductImageStorage::delete($combo->image_path);
        $combo->update([
            'image_path' => ProductImageStorage::store($request->file('image'), $combo->tenant_id, $combo->id),
        ]);
    }
}
