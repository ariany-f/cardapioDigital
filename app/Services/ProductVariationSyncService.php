<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariationGroup;
use App\Models\ProductVariationOption;
use App\Support\TenantContext;

class ProductVariationSyncService
{
    /**
     * @param  array<int, array<string, mixed>>|null  $groups
     */
    public function sync(Product $product, ?array $groups): void
    {
        if ($groups === null) {
            return;
        }

        $tenantId = TenantContext::id() ?? $product->tenant_id;
        $keptGroupIds = [];

        foreach ($groups as $index => $groupData) {
            $name = trim((string) ($groupData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $type = $groupData['type'] ?? ProductVariationGroup::TYPE_CHOICE;
            if (! in_array($type, [
                ProductVariationGroup::TYPE_CHOICE,
                ProductVariationGroup::TYPE_ADDON,
                ProductVariationGroup::TYPE_DISPOSABLE,
            ], true)) {
                $type = ProductVariationGroup::TYPE_CHOICE;
            }

            $group = ProductVariationGroup::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'name' => $name,
                ],
                [
                    'tenant_id' => $tenantId,
                    'type' => $type,
                    'min_select' => max(0, (int) ($groupData['min_select'] ?? 0)),
                    'max_select' => max(1, (int) ($groupData['max_select'] ?? 1)),
                    'allow_quantity' => (bool) ($groupData['allow_quantity'] ?? ($type === ProductVariationGroup::TYPE_DISPOSABLE)),
                    'sort_order' => (int) ($groupData['sort_order'] ?? $index),
                ],
            );

            $keptGroupIds[] = $group->id;
            $keptOptionIds = [];

            foreach ($groupData['options'] ?? [] as $optIndex => $optData) {
                $optName = trim((string) ($optData['name'] ?? ''));
                if ($optName === '') {
                    continue;
                }

                $option = ProductVariationOption::query()->updateOrCreate(
                    [
                        'product_variation_group_id' => $group->id,
                        'name' => $optName,
                    ],
                    [
                        'tenant_id' => $tenantId,
                        'additional_price' => (float) ($optData['additional_price'] ?? 0),
                        'max_quantity' => $this->resolveMaxQuantity($group, $optData),
                        'sort_order' => (int) ($optData['sort_order'] ?? $optIndex),
                    ],
                );

                $keptOptionIds[] = $option->id;
            }

            ProductVariationOption::query()
                ->where('product_variation_group_id', $group->id)
                ->whereNotIn('id', $keptOptionIds)
                ->delete();
        }

        $staleGroups = ProductVariationGroup::query()
            ->where('product_id', $product->id)
            ->when($keptGroupIds !== [], fn ($q) => $q->whereNotIn('id', $keptGroupIds))
            ->get();

        foreach ($staleGroups as $group) {
            $group->options()->delete();
            $group->delete();
        }
    }

    protected function resolveMaxQuantity(ProductVariationGroup $group, array $optData): int
    {
        $max = (int) ($optData['max_quantity'] ?? 0);
        if ($max < 1) {
            $max = $group->allow_quantity ? max(1, (int) $group->max_select) : 1;
        }

        return min(99, max(1, $max));
    }
}
