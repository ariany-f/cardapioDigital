<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;

class StockService
{
    public function decrementForOrder(Product $product, int $quantity, int $branchId, int $orderId, int $tenantId): void
    {
        if (! $product->track_stock) {
            return;
        }

        $product->decrement('stock_quantity', $quantity);

        StockMovement::create([
            'tenant_id' => $tenantId,
            'product_id' => $product->id,
            'branch_id' => $branchId,
            'delta' => -$quantity,
            'reason' => 'order',
            'order_id' => $orderId,
        ]);
    }

    public function restoreForCancelledOrder(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $product = Product::query()->find($item->product_id);

            if (! $product?->track_stock) {
                continue;
            }

            $alreadyRestored = StockMovement::query()
                ->where('order_id', $order->id)
                ->where('product_id', $product->id)
                ->where('reason', 'order_cancelled')
                ->exists();

            if ($alreadyRestored) {
                continue;
            }

            $product->increment('stock_quantity', $item->quantity);

            StockMovement::create([
                'tenant_id' => $order->tenant_id,
                'product_id' => $product->id,
                'branch_id' => $order->branch_id,
                'delta' => $item->quantity,
                'reason' => 'order_cancelled',
                'order_id' => $order->id,
            ]);
        }
    }
}
