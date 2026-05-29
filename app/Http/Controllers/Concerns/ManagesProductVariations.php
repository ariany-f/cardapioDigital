<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Services\ProductVariationSyncService;
use App\Support\MediaUrl;

trait ManagesProductVariations
{
    protected function variationGroupsRules(): array
    {
        return [
            'variation_groups' => ['nullable', 'array'],
            'variation_groups.*.name' => ['required', 'string', 'max:255'],
            'variation_groups.*.type' => ['nullable', 'string', 'in:choice,addon,disposable'],
            'variation_groups.*.min_select' => ['nullable', 'integer', 'min:0', 'max:99'],
            'variation_groups.*.max_select' => ['nullable', 'integer', 'min:1', 'max:99'],
            'variation_groups.*.allow_quantity' => ['boolean'],
            'variation_groups.*.options' => ['nullable', 'array'],
            'variation_groups.*.options.*.name' => ['required', 'string', 'max:255'],
            'variation_groups.*.options.*.additional_price' => ['nullable', 'numeric', 'min:0'],
            'variation_groups.*.options.*.max_quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }

    protected function productWithVariations(Product $product): array
    {
        $product->load([
            'branches:id',
            'variationGroups' => fn ($q) => $q->orderBy('sort_order')->with(['options' => fn ($o) => $o->orderBy('sort_order')]),
        ]);

        return [
            ...$product->toArray(),
            'tags' => implode(', ', $product->tags ?? []),
            'image_url' => MediaUrl::fromPath($product->image_path),
            'branch_ids' => $product->branches->pluck('id'),
            'variation_groups' => $product->variationGroups->map(fn ($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'type' => $g->type,
                'min_select' => $g->min_select,
                'max_select' => $g->max_select,
                'allow_quantity' => $g->allow_quantity,
                'options' => $g->options->map(fn ($o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'additional_price' => $o->additional_price,
                    'max_quantity' => $o->max_quantity,
                ])->values(),
            ])->values(),
        ];
    }

    protected function syncProductVariations(Product $product, ?array $groups): void
    {
        app(ProductVariationSyncService::class)->sync($product, $groups);
    }
}
