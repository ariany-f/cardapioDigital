import { defaultOrderDisposableQty, normalizeOrderDisposables } from '@/composables/useDisposables';
import { formatCepInput } from '@/composables/useDeliveryAddressLookup';

const BRANCH_PREFIX = 'appcardapio:branch:';
const CUSTOMER_PREFIX = 'appcardapio:customer:';

const emptyAddress = () => ({
    street: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: '',
    postal_code: '',
});

function normalizeDeliveryAddress(address) {
    const a = { ...emptyAddress(), ...(address ?? {}) };
    if (a.postal_code) {
        a.postal_code = formatCepInput(a.postal_code);
    }
    return a;
}

export function branchSessionKey(tenantSlug, branchSlug) {
    return `${BRANCH_PREFIX}${tenantSlug}:${branchSlug}`;
}

export function customerSessionKey(tenantSlug) {
    return `${CUSTOMER_PREFIX}${tenantSlug}`;
}

export function loadBranchSession(tenantSlug, branchSlug, branch, options = {}) {
    const lockOrderType = options.lockOrderType === true;
    const defaultOrderType =
        options.defaultOrderType ?? (branch?.delivery_available ? 'delivery' : 'pickup');

    const sessionDefaults = {
        cart: [],
        guestName: '',
        guestPhone: '',
        guestEmail: '',
        orderType: defaultOrderType,
        couponCode: '',
        deliveryAddress: emptyAddress(),
        orderDisposables: defaultOrderDisposableQty(normalizeOrderDisposables(branch?.order_disposables)),
        tipAmount: '',
        scheduledFor: '',
    };

    if (typeof sessionStorage === 'undefined') return sessionDefaults;

    const customer = loadCustomerSession(tenantSlug);

    try {
        const raw = sessionStorage.getItem(branchSessionKey(tenantSlug, branchSlug));
        const saved = raw ? JSON.parse(raw) : null;

        if (!saved) {
            if (customer) {
                return {
                    ...sessionDefaults,
                    guestName: customer.guestName ?? '',
                    guestPhone: customer.guestPhone ?? '',
                    guestEmail: customer.guestEmail ?? '',
                    deliveryAddress: normalizeDeliveryAddress(customer.deliveryAddress),
                };
            }
            return sessionDefaults;
        }

        const config = normalizeOrderDisposables(branch?.order_disposables);
        const qtyDefaults = defaultOrderDisposableQty(config);
        const disposables = { ...qtyDefaults, ...(saved.orderDisposables ?? {}) };
        for (const item of config) {
            const qty = parseInt(disposables[item.key], 10) || 0;
            disposables[item.key] = Math.max(item.min_qty, Math.min(item.max_qty, qty));
        }

        return {
            cart: Array.isArray(saved.cart) ? saved.cart : [],
            guestName: saved.guestName ?? customer?.guestName ?? '',
            guestPhone: saved.guestPhone ?? customer?.guestPhone ?? '',
            guestEmail: saved.guestEmail ?? customer?.guestEmail ?? '',
            orderType: lockOrderType ? defaultOrderType : saved.orderType ?? defaultOrderType,
            couponCode: saved.couponCode ?? '',
            deliveryAddress: normalizeDeliveryAddress({
                ...customer?.deliveryAddress,
                ...saved.deliveryAddress,
            }),
            orderDisposables: disposables,
            tipAmount: saved.tipAmount ?? '',
            scheduledFor: saved.scheduledFor ?? '',
        };
    } catch {
        return sessionDefaults;
    }
}

export function saveBranchSession(tenantSlug, branchSlug, data) {
    if (typeof sessionStorage === 'undefined') return;
    try {
        sessionStorage.setItem(
            branchSessionKey(tenantSlug, branchSlug),
            JSON.stringify({
                cart: data.cart ?? [],
                guestName: data.guestName ?? '',
                guestPhone: data.guestPhone ?? '',
                guestEmail: data.guestEmail ?? '',
                orderType: data.orderType ?? 'pickup',
                couponCode: data.couponCode ?? '',
                deliveryAddress: normalizeDeliveryAddress(data.deliveryAddress),
                orderDisposables: data.orderDisposables ?? {},
                tipAmount: data.tipAmount ?? '',
                scheduledFor: data.scheduledFor ?? '',
                savedAt: Date.now(),
            }),
        );
        saveCustomerSession(tenantSlug, {
            guestName: data.guestName,
            guestPhone: data.guestPhone,
            guestEmail: data.guestEmail,
            deliveryAddress: normalizeDeliveryAddress(data.deliveryAddress),
        });
    } catch {
        /* ignore */
    }
}

export function loadCustomerSession(tenantSlug) {
    if (typeof sessionStorage === 'undefined') return null;
    try {
        const raw = sessionStorage.getItem(customerSessionKey(tenantSlug));
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

export function saveCustomerSession(tenantSlug, data) {
    if (typeof sessionStorage === 'undefined') return;
    try {
        sessionStorage.setItem(
            customerSessionKey(tenantSlug),
            JSON.stringify({
                guestName: data.guestName ?? '',
                guestPhone: data.guestPhone ?? '',
                deliveryAddress: normalizeDeliveryAddress(data.deliveryAddress),
                savedAt: Date.now(),
            }),
        );
    } catch {
        /* quota or private mode */
    }
}

export function clearBranchSession(tenantSlug, branchSlug) {
    if (typeof sessionStorage === 'undefined') return;
    try {
        sessionStorage.removeItem(branchSessionKey(tenantSlug, branchSlug));
    } catch {
        /* ignore */
    }
}
