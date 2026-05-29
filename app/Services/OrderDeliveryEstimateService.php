<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;

class OrderDeliveryEstimateService
{
    /**
     * Maior tempo de preparo entre os produtos do pedido; se nenhum produto tiver tempo, usa o padrão da filial.
     *
     * @param  list<array{product_id?: int|null, combo_id?: int|null}>  $items
     */
    public function estimatePrepMinutes(Branch $branch, array $items): int
    {
        $default = max(1, (int) ($branch->default_prep_time_minutes ?: 30));

        $productIds = collect($items)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return $default;
        }

        $explicit = Product::query()
            ->whereIn('id', $productIds)
            ->whereNotNull('prep_time_minutes')
            ->where('prep_time_minutes', '>', 0)
            ->pluck('prep_time_minutes');

        if ($explicit->isEmpty()) {
            return $default;
        }

        return (int) $explicit->max();
    }

    /**
     * @param  list<array{product_id?: int|null, combo_id?: int|null}>  $items
     */
    public function estimateDeliveryMinutes(Branch $branch, array $items): int
    {
        return $this->estimatePrepMinutes($branch, $items)
            + max(0, (int) ($branch->delivery_time_minutes ?? 0));
    }

    /**
     * @param  list<array{product_id?: int|null, combo_id?: int|null}>  $items
     */
    public function estimateTotalMinutes(Branch $branch, string $orderType, array $items): int
    {
        $prep = $this->estimatePrepMinutes($branch, $items);

        if ($orderType === 'delivery') {
            return $prep + max(0, (int) ($branch->delivery_time_minutes ?? 0));
        }

        return $prep;
    }
}
