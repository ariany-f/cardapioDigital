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

export function useAdminOrdersPending() {
    const page = usePage();
    const { can } = usePermissions();
    const tenant = computed(() => page.props.tenant);
    const enabled = computed(() => !!tenant.value?.slug && can('orders.view'));

    const pendingTotal = ref(0);
    let pollTimer = null;
    let initialized = false;
    let lastTotal = 0;

    const fetchPending = async () => {
        if (!enabled.value) return;

        try {
            const { data } = await axios.get(
                route('tenant.admin.orders.pending-count', { tenant: tenant.value.slug }),
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
                        tag: `orders-pending-${tenant.value.slug}`,
                        onClick: () => {
                            const base = route('tenant.admin.orders.index', {
                                tenant: tenant.value.slug,
                            });
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

    const start = () => {
        stop();
        if (!enabled.value) return;
        pollTimer = setInterval(fetchPending, POLL_MS);
        fetchPending();
    };

    const stop = () => {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    };

    onMounted(() => {
        ensureChatNotificationPermission();
        start();
    });

    onUnmounted(stop);

    watch(enabled, (ok) => {
        if (ok) {
            initialized = false;
            lastTotal = 0;
            start();
        } else {
            stop();
            pendingTotal.value = 0;
        }
    });

    return { pendingTotal, refreshPending: fetchPending };
}
