import { ensureChatNotificationPermission, notifyNewChatMessage, playNewChatSound } from '@/composables/useChatNotify';
import { usePermissions } from '@/composables/usePermissions';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const POLL_MS = 5000;

export function useAdminChatUnread() {
    const page = usePage();
    const { can } = usePermissions();
    const tenant = computed(() => page.props.tenant);
    const enabled = computed(() => !!tenant.value?.slug && can('chat.access'));

    const totalUnread = ref(0);
    let pollTimer = null;
    let initialized = false;
    let lastTotal = 0;

    const fetchUnread = async () => {
        if (!enabled.value) return;

        try {
            const { data } = await axios.get(
                route('tenant.admin.chat.unread', { tenant: tenant.value.slug }),
            );
            const total = data.total ?? 0;
            totalUnread.value = total;

            if (initialized && total > lastTotal) {
                const diff = total - lastTotal;
                const onChatPage = route().current('tenant.admin.chat.index');
                if (!onChatPage || document.hidden) {
                    playNewChatSound();
                    notifyNewChatMessage({
                        title: 'Nova mensagem no chat',
                        body: diff === 1 ? '1 mensagem não lida' : `${diff} mensagens não lidas`,
                        tag: `chat-unread-${tenant.value.slug}`,
                        onClick: () => {
                            window.location.href = route('tenant.admin.chat.index', { tenant: tenant.value.slug });
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
        pollTimer = setInterval(fetchUnread, POLL_MS);
        fetchUnread();
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
            totalUnread.value = 0;
        }
    });

    return { totalUnread, refreshUnread: fetchUnread };
}
