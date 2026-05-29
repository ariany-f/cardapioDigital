<?php

namespace App\Support;

class OrderDisposableConfig
{
    /**
     * Normaliza itens de descartáveis do pedido (nível filial).
     *
     * @param  array<int, array<string, mixed>>|null  $items
     * @return array<int, array{key: string, label: string, min_qty: int, max_qty: int, default_qty: int}>
     */
    public static function normalizeList(?array $items): array
    {
        $out = [];

        foreach ($items ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $key = $item['key'] ?? null;
            if (! is_string($key) || $key === '') {
                continue;
            }

            $maxQty = max(0, (int) ($item['max_qty'] ?? $item['max_quantity'] ?? 10));
            $minQty = max(0, (int) ($item['min_qty'] ?? 0));
            if ($minQty > $maxQty) {
                $minQty = 0;
            }

            $defaultQty = (int) ($item['default_qty'] ?? 0);
            if ($defaultQty === 0 && ! empty($item['default'])) {
                $defaultQty = $minQty > 0 ? $minQty : 1;
            }
            $defaultQty = max($minQty, min($maxQty, $defaultQty));

            $out[] = [
                'key' => $key,
                'label' => (string) ($item['label'] ?? $key),
                'min_qty' => $minQty,
                'max_qty' => $maxQty,
                'default_qty' => $defaultQty,
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array{key: string, label: string, min_qty: int, max_qty: int, default_qty: int}>  $config
     * @return array<string, int>
     */
    public static function defaultSelection(array $config): array
    {
        $selection = [];
        foreach ($config as $item) {
            $selection[$item['key']] = $item['default_qty'];
        }

        return $selection;
    }

    /**
     * @param  array<int, array{key: string, label: string, min_qty: int, max_qty: int, default_qty: int}>  $config
     * @param  array<string, mixed>  $selected
     * @return array<string, int>
     */
    public static function validateSelection(array $config, array $selected): array
    {
        $validated = [];

        foreach ($config as $item) {
            $key = $item['key'];
            $qty = (int) ($selected[$key] ?? $item['default_qty']);
            $validated[$key] = max($item['min_qty'], min($item['max_qty'], $qty));
        }

        return $validated;
    }

    /**
     * @param  array<int, array{key: string, label: string, min_qty: int, max_qty: int, default_qty: int}>  $config
     * @param  array<string, int>  $selected
     * @return array<int, array{key: string, label: string, quantity: int, requested: bool}>|null
     */
    public static function buildSnapshot(array $config, array $selected): ?array
    {
        if ($config === []) {
            return null;
        }

        $snapshot = [];
        foreach ($config as $item) {
            $qty = (int) ($selected[$item['key']] ?? 0);
            $snapshot[] = [
                'key' => $item['key'],
                'label' => $item['label'],
                'quantity' => $qty,
                'requested' => $qty > 0,
            ];
        }

        return $snapshot;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $items
     * @return array<int, array<string, mixed>>
     */
    public static function sanitizeForStorage(?array $items): array
    {
        return self::normalizeList($items);
    }
}
