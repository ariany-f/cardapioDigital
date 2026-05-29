<?php

namespace App\Http\Controllers\Concerns;

use App\Support\MediaUrl;

trait ValidatesProductCatalog
{
    protected function productFieldsRules(bool $withVariations = false): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'is_paused' => ['boolean'],
            'is_featured' => ['boolean'],
            'track_stock' => ['boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'tags' => ['nullable', 'string', 'max:500'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
            ...$this->productImageRules(),
            ...($withVariations ? $this->variationGroupsRules() : []),
        ];
    }

    protected function productAttributes(array $data): array
    {
        $tags = array_values(array_filter(array_map('trim', explode(',', $data['tags'] ?? ''))));

        return [
            'name' => $data['name'],
            'category_id' => $data['category_id'],
            'description' => $data['description'] ?? null,
            'base_price' => $data['base_price'],
            'is_active' => $data['is_active'] ?? true,
            'is_paused' => $data['is_paused'] ?? false,
            'is_featured' => $data['is_featured'] ?? false,
            'track_stock' => $data['track_stock'] ?? false,
            'stock_quantity' => ($data['track_stock'] ?? false) ? ($data['stock_quantity'] ?? 0) : null,
            'tags' => $tags ?: null,
            'prep_time_minutes' => isset($data['prep_time_minutes']) && $data['prep_time_minutes'] !== ''
                ? (int) $data['prep_time_minutes']
                : null,
        ];
    }

    protected function productsListPayload()
    {
        return \App\Models\Product::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->paginate(20)
            ->through(fn ($p) => [
                ...$p->toArray(),
                'tags' => implode(', ', $p->tags ?? []),
                'image_url' => MediaUrl::fromPath($p->image_path),
            ]);
    }
}
