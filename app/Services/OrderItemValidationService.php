<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariationOption;
use Illuminate\Validation\ValidationException;

class OrderItemValidationService
{
    public function validate(array $items, Branch $branch): void
    {
        foreach ($items as $item) {
            if (! empty($item['combo_id'])) {
                $combo = \App\Models\Combo::query()
                    ->where('id', $item['combo_id'])
                    ->where('is_active', true)
                    ->where(function ($q) use ($branch) {
                        $q->where('branch_id', $branch->id)->orWhereNull('branch_id');
                    })
                    ->first();

                if (! $combo || abs((float) $item['unit_price'] - (float) $combo->price) > 0.02) {
                    throw ValidationException::withMessages(['items' => ['Combo inválido ou indisponível.']]);
                }

                continue;
            }

            $product = Product::query()
                ->where('id', $item['product_id'])
                ->where('is_active', true)
                ->where('is_paused', false)
                ->whereHas('branches', fn ($q) => $q->where('branches.id', $branch->id))
                ->first();

            if (! $product || ! $product->isAvailable()) {
                throw ValidationException::withMessages([
                    'items' => ['Um ou mais itens não estão disponíveis nesta unidade.'],
                ]);
            }

            if ($product->track_stock && $product->stock_quantity < $item['quantity']) {
                throw ValidationException::withMessages([
                    'items' => [sprintf('Estoque insuficiente para "%s".', $product->name)],
                ]);
            }

            $expected = (float) $product->base_price;
            foreach ($item['variations'] ?? [] as $variation) {
                $option = ProductVariationOption::query()
                    ->where('id', $variation['option_id'])
                    ->with('group')
                    ->whereHas('group', fn ($q) => $q->where('product_id', $product->id))
                    ->first();
                if (! $option) {
                    throw ValidationException::withMessages([
                        'items' => ['Opção de variação inválida.'],
                    ]);
                }

                $qty = max(1, (int) ($variation['quantity'] ?? 1));
                $group = $option->group;
                if ($group->allow_quantity || $group->type === 'disposable') {
                    $maxQty = (int) ($option->max_quantity ?? $group->max_select ?? 1);
                    if ($qty > $maxQty) {
                        throw ValidationException::withMessages([
                            'items' => [sprintf('Quantidade máxima para "%s" é %d.', $option->name, $maxQty)],
                        ]);
                    }
                } else {
                    $qty = 1;
                }

                $expected += (float) $option->additional_price * $qty;
            }

            if (abs((float) $item['unit_price'] - $expected) > 0.01) {
                throw ValidationException::withMessages([
                    'items' => ['Preço do item desatualizado. Atualize o cardápio e tente novamente.'],
                ]);
            }
        }
    }
}
