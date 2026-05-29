const CHAT_RECENT_DAYS = 15;

const purchasedKey = (tenantSlug, branchSlug) =>
    `appcardapio:chat_purchased:${tenantSlug}:${branchSlug}`;

const isRecentTimestamp = (iso) => {
    const at = new Date(iso);
    if (Number.isNaN(at.getTime())) {
        return false;
    }

    const cutoff = Date.now() - CHAT_RECENT_DAYS * 24 * 60 * 60 * 1000;

    return at.getTime() >= cutoff;
};

export function markChatPurchasedLocal(tenantSlug, branchSlug, purchasedAt = new Date().toISOString()) {
    if (typeof localStorage === 'undefined') {
        return;
    }
    try {
        localStorage.setItem(purchasedKey(tenantSlug, branchSlug), purchasedAt);
    } catch {
        /* ignore */
    }
}

export function hasChatPurchasedLocal(tenantSlug, branchSlug) {
    if (typeof localStorage === 'undefined') {
        return false;
    }
    try {
        const raw = localStorage.getItem(purchasedKey(tenantSlug, branchSlug));
        if (!raw || raw === '1') {
            return false;
        }

        return isRecentTimestamp(raw);
    } catch {
        return false;
    }
}
