/**
 * Descartáveis do pedido (nível filial) — normalização e helpers de quantidade.
 */

export function normalizeOrderDisposables(items) {
    if (!Array.isArray(items)) return [];

    return items
        .filter((item) => item && typeof item.key === 'string' && item.key !== '')
        .map((item) => {
            let maxQty = Math.max(0, parseInt(item.max_qty ?? item.max_quantity ?? 10, 10) || 0);
            let minQty = Math.max(0, parseInt(item.min_qty ?? 0, 10) || 0);
            if (minQty > maxQty) minQty = 0;

            let defaultQty = parseInt(item.default_qty ?? 0, 10) || 0;
            if (defaultQty === 0 && item.default) {
                defaultQty = minQty > 0 ? minQty : 1;
            }
            defaultQty = Math.max(minQty, Math.min(maxQty, defaultQty));

            return {
                key: item.key,
                label: item.label || item.key,
                min_qty: minQty,
                max_qty: maxQty,
                default_qty: defaultQty,
            };
        });
}

export function defaultOrderDisposableQty(items) {
    const next = {};
    for (const item of normalizeOrderDisposables(items)) {
        next[item.key] = item.default_qty;
    }
    return next;
}

export function clampDisposableQty(item, qty) {
    const n = parseInt(qty, 10) || 0;
    return Math.max(item.min_qty, Math.min(item.max_qty, n));
}

export function formatVariationLabel(variation) {
    const qty = variation.quantity ?? 1;
    return qty > 1 ? `${variation.option_name} ×${qty}` : variation.option_name;
}
