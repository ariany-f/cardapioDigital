<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ensureChatNotificationPermission,
    notifyNewChatMessage,
    playNewChatSound,
} from '@/composables/useChatNotify';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    conversations: Array,
    filters: Object,
    branches: Array,
});

const page = usePage();
const tenant = page.props.tenant;

const list = ref([...props.conversations]);
const activeUuid = ref(null);
const messages = ref([]);
const draft = ref('');
const loading = ref(false);
const sending = ref(false);
const messagesEl = ref(null);
let threadPollTimer = null;
let listPollTimer = null;
const lastUnreadByUuid = ref({});

const POLL_THREAD_MS = 2000;
const POLL_LIST_MS = 4000;

const initUnreadMap = () => {
    const map = {};
    list.value.forEach((c) => {
        map[c.uuid] = c.staff_unread_count ?? 0;
    });
    lastUnreadByUuid.value = map;
};

const notifyCustomerMessage = (displayName, body, uuid) => {
    playNewChatSound();
    notifyNewChatMessage({
        title: displayName || 'Cliente',
        body: body?.length > 90 ? `${body.slice(0, 90)}…` : body || 'Nova mensagem',
        tag: `chat-staff-${uuid}`,
    });
};

const trackListUnread = (conversations) => {
    conversations.forEach((c) => {
        const prev = lastUnreadByUuid.value[c.uuid] ?? 0;
        const next = c.staff_unread_count ?? 0;
        if (next > prev && c.uuid !== activeUuid.value) {
            notifyCustomerMessage(c.display_name, null, c.uuid);
        }
        lastUnreadByUuid.value[c.uuid] = next;
    });
};

const activeConversation = () => list.value.find((c) => c.uuid === activeUuid.value);

const lastMessageId = () => {
    if (!messages.value.length) return null;
    return messages.value[messages.value.length - 1].id;
};

const scrollBottom = async () => {
    await nextTick();
    if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
};

const mergeMessages = async (incoming) => {
    if (!incoming?.length) return;
    const ids = new Set(messages.value.map((m) => m.id));
    const added = [];
    incoming.forEach((m) => {
        if (!ids.has(m.id)) {
            messages.value.push(m);
            added.push(m);
        }
    });
    if (!added.length) return;

    const fromCustomer = added.filter((m) => m.sender_type === 'customer');
    if (fromCustomer.length && document.hidden) {
        const last = fromCustomer[fromCustomer.length - 1];
        notifyCustomerMessage(activeConversation()?.display_name, last.body, activeUuid.value);
    }

    await scrollBottom();
};

const pollList = async () => {
    try {
        const { data } = await axios.get(
            route('tenant.admin.chat.updates', { tenant: tenant.slug }),
            { params: { status: props.filters?.status ?? 'open' } },
        );
        if (!data.conversations) return;

        const byUuid = Object.fromEntries(data.conversations.map((c) => [c.uuid, c]));
        list.value = list.value.map((c) => byUuid[c.uuid] ?? c);
        data.conversations.forEach((c) => {
            if (!list.value.some((x) => x.uuid === c.uuid)) {
                list.value.unshift(c);
            }
        });
        list.value.sort((a, b) => {
            const ta = a.last_message_at ? new Date(a.last_message_at).getTime() : 0;
            const tb = b.last_message_at ? new Date(b.last_message_at).getTime() : 0;
            return tb - ta;
        });
        trackListUnread(data.conversations);
    } catch {
        // ignora falha pontual
    }
};

const loadThread = async (uuid) => {
    activeUuid.value = uuid;
    loading.value = true;
    try {
        const { data } = await axios.get(
            route('tenant.admin.chat.messages', { tenant: tenant.slug, uuid }),
        );
        messages.value = data.messages ?? [];
        const idx = list.value.findIndex((c) => c.uuid === uuid);
        if (idx >= 0) {
            list.value[idx] = { ...list.value[idx], ...data.conversation, staff_unread_count: 0 };
            lastUnreadByUuid.value[uuid] = 0;
        }
        await scrollBottom();
        startThreadPolling();
        pollThread();
    } finally {
        loading.value = false;
    }
};

const pollThread = async () => {
    if (!activeUuid.value) return;

    try {
        const afterId = lastMessageId();
        const { data } = await axios.get(
            route('tenant.admin.chat.messages', { tenant: tenant.slug, uuid: activeUuid.value }),
            { params: afterId ? { after_id: afterId } : {} },
        );
        await mergeMessages(data.messages);
        if (data.conversation) {
            const idx = list.value.findIndex((c) => c.uuid === activeUuid.value);
            if (idx >= 0) {
                list.value[idx] = { ...list.value[idx], ...data.conversation };
            }
        }
    } catch {
        // ignora falha pontual
    }
};

const send = async () => {
    const body = draft.value.trim();
    if (!body || !activeUuid.value) return;
    sending.value = true;
    try {
        const { data } = await axios.post(
            route('tenant.admin.chat.send', { tenant: tenant.slug, uuid: activeUuid.value }),
            { body },
        );
        messages.value.push(data.message);
        draft.value = '';
        await scrollBottom();
        await pollThread();
        await pollList();
    } finally {
        sending.value = false;
    }
};

const closeChat = async () => {
    if (!activeUuid.value || !confirm('Encerrar esta conversa?')) return;
    await axios.post(route('tenant.admin.chat.close', { tenant: tenant.slug, uuid: activeUuid.value }));
    const c = activeConversation();
    if (c) c.status = 'closed';
    stopThreadPolling();
};

const startThreadPolling = () => {
    stopThreadPolling();
    threadPollTimer = setInterval(pollThread, POLL_THREAD_MS);
};

const stopThreadPolling = () => {
    if (threadPollTimer) {
        clearInterval(threadPollTimer);
        threadPollTimer = null;
    }
};

const startListPolling = () => {
    stopListPolling();
    listPollTimer = setInterval(pollList, POLL_LIST_MS);
};

const stopListPolling = () => {
    if (listPollTimer) {
        clearInterval(listPollTimer);
        listPollTimer = null;
    }
};

watch(activeUuid, (uuid) => {
    if (!uuid) stopThreadPolling();
});

onMounted(() => {
    ensureChatNotificationPermission();
    initUnreadMap();
    startListPolling();
    pollList();
});

onUnmounted(() => {
    stopThreadPolling();
    stopListPolling();
});

const formatTime = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Chat com clientes" />

    <h1 class="admin-page-title">Chat com clientes</h1>
    <p class="mt-1 text-sm text-stone-500">
        As mensagens são atualizadas automaticamente. Mantenha a conversa aberta para ver respostas do cliente.
    </p>

    <div class="admin-card mt-6 flex min-h-[28rem] overflow-hidden p-0">
        <aside class="w-full max-w-xs shrink-0 border-r border-stone-100 bg-stone-50">
            <p class="border-b border-stone-100 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-stone-500">
                Conversas abertas
            </p>
            <ul class="max-h-[26rem] overflow-y-auto">
                <li v-if="!list.length" class="px-4 py-8 text-center text-sm text-stone-400">Nenhuma conversa ainda.</li>
                <li v-for="c in list" :key="c.uuid">
                    <button
                        type="button"
                        class="w-full border-b border-stone-100 px-4 py-3 text-left transition hover:bg-white"
                        :class="activeUuid === c.uuid ? 'bg-white ring-1 ring-inset ring-orange-200' : ''"
                        @click="loadThread(c.uuid)"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <span class="font-medium text-stone-900">{{ c.display_name }}</span>
                            <span
                                v-if="c.staff_unread_count > 0"
                                class="shrink-0 rounded-full bg-orange-500 px-1.5 py-0.5 text-[10px] font-bold text-white"
                            >
                                {{ c.staff_unread_count }}
                            </span>
                        </div>
                        <p class="text-xs text-stone-500">{{ c.branch?.name }}</p>
                        <p v-if="c.guest_phone" class="text-xs text-stone-400">{{ c.guest_phone }}</p>
                        <p class="mt-1 text-[10px] text-stone-400">{{ formatTime(c.last_message_at) }}</p>
                    </button>
                </li>
            </ul>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <template v-if="activeUuid">
                <div class="flex items-center justify-between border-b border-stone-100 px-4 py-3">
                    <div>
                        <p class="font-semibold text-stone-900">{{ activeConversation()?.display_name }}</p>
                        <p class="text-xs text-stone-500">{{ activeConversation()?.branch?.name }}</p>
                    </div>
                    <button
                        v-if="activeConversation()?.status === 'open'"
                        type="button"
                        class="text-sm text-red-600 hover:underline"
                        @click="closeChat"
                    >
                        Encerrar
                    </button>
                </div>
                <div ref="messagesEl" class="flex-1 space-y-2 overflow-y-auto p-4">
                    <p v-if="loading" class="text-center text-sm text-stone-400">Carregando…</p>
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="flex"
                        :class="msg.sender_type === 'staff' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[80%] rounded-2xl px-3 py-2 text-sm"
                            :class="
                                msg.sender_type === 'staff'
                                    ? 'rounded-br-md bg-orange-500 text-white'
                                    : 'rounded-bl-md bg-stone-100 text-stone-800'
                            "
                        >
                            <p v-if="msg.sender_type === 'staff'" class="mb-0.5 text-[10px] text-orange-100">
                                {{ msg.sender_name }}
                            </p>
                            {{ msg.body }}
                        </div>
                    </div>
                </div>
                <form
                    v-if="activeConversation()?.status === 'open'"
                    class="flex gap-2 border-t border-stone-100 p-3"
                    @submit.prevent="send"
                >
                    <input
                        v-model="draft"
                        type="text"
                        class="admin-input flex-1"
                        placeholder="Responder ao cliente…"
                    />
                    <button type="submit" class="admin-btn-primary shrink-0" :disabled="sending">Enviar</button>
                </form>
                <p v-else class="border-t border-stone-100 p-4 text-center text-sm text-stone-500">Conversa encerrada.</p>
            </template>
            <p v-else class="flex flex-1 items-center justify-center text-sm text-stone-400">
                Selecione uma conversa à esquerda.
            </p>
        </div>
    </div>
</template>
