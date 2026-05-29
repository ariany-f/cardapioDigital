/**
 * @param {Object} params
 * @param {string} params.orderType
 * @param {Array<{ product_id?: number, combo_id?: number }>} params.cart
 * @param {{ default_prep_time_minutes?: number, delivery_time_minutes?: number }} params.branch
 * @param {Map<number, { prep_time_minutes?: number|null }>} params.productsById
 * @returns {number|null}
 */
export function estimateOrderMinutes({ orderType, cart, branch, productsById }) {
    if (!cart?.length || !branch) {
        return null;
    }

    const defaultPrep = Math.max(1, Number(branch.default_prep_time_minutes) || 30);
    const deliveryLeg = Math.max(0, Number(branch.delivery_time_minutes) || 0);

    const prepTimes = [];
    for (const line of cart) {
        if (!line.product_id) {
            continue;
        }
        const product = productsById?.get?.(line.product_id);
        const minutes = Number(product?.prep_time_minutes);
        if (Number.isFinite(minutes) && minutes > 0) {
            prepTimes.push(minutes);
        }
    }

    const prep = prepTimes.length ? Math.max(...prepTimes) : defaultPrep;

    if (orderType === 'delivery') {
        return prep + deliveryLeg;
    }

    if (orderType === 'pickup' || orderType === 'dine_in') {
        return prep;
    }

    return null;
}

export function formatEstimateMinutes(minutes) {
    const value = Number(minutes);
    if (!Number.isFinite(value) || value <= 0) {
        return '';
    }

    return `~${Math.round(value)} min`;
}
