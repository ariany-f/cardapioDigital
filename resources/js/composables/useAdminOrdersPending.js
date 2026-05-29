import {
    ensureChatNotificationPermission,
    notifyNewChatMessage,
    playNewChatSound,
} from '@/composables/useChatNotify';
import { usePermissions } from '@/composables/usePermissions';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const POLL_MS = 5000;

const pendingTotal = ref(0);
let pollTimer = null;
let subscriberCount = 0;
let initialized = false;
let lastTotal = 0;
let lastTenantSlug = null;

const fetchPending = async (tenantSlug, enabled) => {
    if (!enabled || !tenantSlug) return;

    try {
        const { data } = await axios.get(
            route('tenant.admin.orders.pending-count', { tenant: tenantSlug }),
        );
        const total = data.total ?? 0;
        pendingTotal.value = total;

        if (initialized && total > lastTotal) {
            const diff = total - lastTotal;
            const onOrdersPage =
                route().current('tenant.admin.orders.index') ||
                route().current('tenant.admin.orders.show');
            if (!onOrdersPage || document.hidden) {
                playNewChatSound();
                notifyNewChatMessage({
                    title: 'Novo pedido',
                    body: diff === 1 ? '1 pedido aguardando confirmação' : `${diff} pedidos aguardando confirmação`,
                    tag: `orders-pending-${tenantSlug}`,
                    onClick: () => {
                        const base = route('tenant.admin.orders.index', { tenant: tenantSlug });
                        window.location.href = `${base}?status=pending_approval`;
                    },
                });
            }
        }

        lastTotal = total;
        initialized = true;
    } catch {
        /* ignora falha pontual */
    }
};

const stopPolling = () => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

export function useAdminOrdersPending() {
    const page = usePage();
    const { can } = usePermissions();
    const tenant = computed(() => page.props.tenant);
    const enabled = computed(() => !!tenant.value?.slug && can('orders.view'));

    const startPolling = () => {
        stopPolling();
        if (!enabled.value) return;

        const slug = tenant.value.slug;
        const tick = () => fetchPending(slug, true);

        pollTimer = setInterval(tick, POLL_MS);
        tick();
    };

    const resetState = () => {
        initialized = false;
        lastTotal = 0;
        pendingTotal.value = 0;
    };

    onMounted(() => {
        subscriberCount += 1;
        if (subscriberCount === 1) {
            ensureChatNotificationPermission();
        }
        if (enabled.value) {
            if (lastTenantSlug !== tenant.value?.slug) {
                resetState();
                lastTenantSlug = tenant.value.slug;
            }
            startPolling();
        }
    });

    onUnmounted(() => {
        subscriberCount -= 1;
        if (subscriberCount === 0) {
            stopPolling();
        }
    });

    watch(enabled, (ok) => {
        if (ok) {
            resetState();
            lastTenantSlug = tenant.value?.slug ?? null;
            if (subscriberCount > 0) {
                startPolling();
            }
        } else {
            stopPolling();
            resetState();
            lastTenantSlug = null;
        }
    });

    watch(
        () => tenant.value?.slug,
        (slug, prev) => {
            if (!enabled.value || !slug || slug === prev) return;
            resetState();
            lastTenantSlug = slug;
            if (subscriberCount > 0) {
                startPolling();
            }
        },
    );

    const refreshPending = () => {
        if (enabled.value && tenant.value?.slug) {
            return fetchPending(tenant.value.slug, true);
        }
    };

    return { pendingTotal, refreshPending };
}
