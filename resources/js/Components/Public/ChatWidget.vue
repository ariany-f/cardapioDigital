<script setup>
import NavIcon from '@/Components/NavIcon.vue';
import {
    ensureChatNotificationPermission,
    notifyNewChatMessage,
    playNewChatSound,
} from '@/composables/useChatNotify';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    tenantSlug: { type: String, required: true },
    branchSlug: { type: String, required: true },
    enabled: { type: Boolean, default: false },
    initialGuestName: { type: String, default: '' },
    initialGuestPhone: { type: String, default: '' },
});

const page = usePage();
const customer = computed(() => page.props.auth?.customer);

const storageKey = (suffix) => `chat_${suffix}_${props.tenantSlug}_${props.branchSlug}`;

const open = ref(false);
const loading = ref(false);
const sending = ref(false);
const conversation = ref(null);
const guestKey = ref(localStorage.getItem(storageKey('guest')) || '');
const guestName = ref('');
const guestPhone = ref('');
const messages = ref([]);
const draft = ref('');
const messagesEl = ref(null);
let pollTimer = null;

const POLL_MS = 2000;

const hasGuestIdentity = computed(
    () => Boolean(props.initialGuestName?.trim() || guestName.value.trim()),
);

const needsGuestInfo = computed(
    () =>
        props.enabled &&
        !customer.value &&
        !guestKey.value &&
        !conversation.value &&
        !hasGuestIdentity.value,
);

const unreadCount = computed(() => conversation.value?.customer_unread_count ?? 0);

const chatHeaders = () => (guestKey.value ? { 'X-Chat-Guest-Key': guestKey.value } : {});

const chatParams = (extra = {}) => ({
    ...(guestKey.value ? { guest_key: guestKey.value } : {}),
    ...extra,
});

const lastMessageId = () => {
    if (!messages.value.length) return null;
    return messages.value[messages.value.length - 1].id;
};

const persistSession = (conv, key) => {
    if (conv?.uuid) {
        localStorage.setItem(storageKey('conv'), conv.uuid);
    }
    if (key) {
        guestKey.value = key;
        localStorage.setItem(storageKey('guest'), key);
    }
};

const scrollBottom = async () => {
    await nextTick();
    if (messagesEl.value) {
        messagesEl.value.scrollTop = messagesEl.value.scrollHeight;
    }
};

const notifyIncomingStaff = (incoming) => {
    const staffMessages = incoming.filter((m) => m.sender_type === 'staff');
    if (!staffMessages.length) return;

    if (open.value && !document.hidden) return;

    playNewChatSound();
    const last = staffMessages[staffMessages.length - 1];
    notifyNewChatMessage({
        title: 'Nova mensagem',
        body: last.body?.length > 80 ? `${last.body.slice(0, 80)}…` : last.body,
        tag: `chat-customer-${conversation.value?.uuid}`,
        onClick: () => {
            open.value = true;
        },
    });
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
    if (added.length) {
        notifyIncomingStaff(added);
        if (open.value) await scrollBottom();
    }
};

const pollMessages = async () => {
    if (!conversation.value?.uuid) return;

    try {
        const afterId = lastMessageId();
        const { data } = await axios.get(
            route('tenant.chat.messages', { tenant: props.tenantSlug, uuid: conversation.value.uuid }),
            {
                params: chatParams({
                    ...(afterId ? { after_id: afterId } : {}),
                    ...(open.value ? { mark_read: 1 } : {}),
                }),
                headers: chatHeaders(),
            },
        );

        await mergeMessages(data.messages);
        if (data.conversation) {
            conversation.value = data.conversation;
        }
    } catch {
        /* ignora falha pontual de rede */
    }
};

const startChat = async () => {
    loading.value = true;
    try {
        const { data } = await axios.post(
            route('tenant.chat.start', { tenant: props.tenantSlug, branch: props.branchSlug }),
            {
                guest_name: customer.value?.name ?? guestName.value,
                guest_phone: customer.value?.phone ?? guestPhone.value,
                guest_key: guestKey.value || undefined,
                conversation_uuid: conversation.value?.uuid || localStorage.getItem(storageKey('conv')) || undefined,
            },
            { headers: chatHeaders() },
        );
        conversation.value = data.conversation;
        persistSession(data.conversation, data.guest_key);
        await loadMessages();
        startPolling();
    } finally {
        loading.value = false;
    }
};

const loadMessages = async () => {
    if (!conversation.value?.uuid) return;

    const { data } = await axios.get(
        route('tenant.chat.messages', { tenant: props.tenantSlug, uuid: conversation.value.uuid }),
        {
            params: chatParams({ mark_read: 1 }),
            headers: chatHeaders(),
        },
    );
    messages.value = data.messages ?? [];
    conversation.value = data.conversation;
    persistSession(data.conversation, guestKey.value);
    await scrollBottom();
};

const send = async () => {
    const body = draft.value.trim();
    if (!body || !conversation.value?.uuid) return;

    sending.value = true;
    try {
        const { data } = await axios.post(
            route('tenant.chat.send', { tenant: props.tenantSlug, uuid: conversation.value.uuid }),
            { body, guest_key: guestKey.value || undefined },
            { headers: chatHeaders() },
        );
        messages.value.push(data.message);
        draft.value = '';
        await scrollBottom();
        await pollMessages();
    } finally {
        sending.value = false;
    }
};

const startPolling = () => {
    stopPolling();
    pollTimer = setInterval(pollMessages, POLL_MS);
};

const stopPolling = () => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
};

const toggle = async () => {
    if (!props.enabled) return;

    open.value = !open.value;
    if (open.value) {
        ensureChatNotificationPermission();
        if (!conversation.value && (customer.value || guestKey.value || hasGuestIdentity.value)) {
            await startChat();
        } else if (conversation.value) {
            await loadMessages();
            startPolling();
            pollMessages();
        }
    }
};

watch(open, (isOpen) => {
    if (isOpen && conversation.value) {
        loadMessages();
        startPolling();
    }
});

onMounted(async () => {
    if (!props.enabled) return;

    if (props.initialGuestName) {
        guestName.value = props.initialGuestName;
    }
    if (props.initialGuestPhone) {
        guestPhone.value = props.initialGuestPhone;
    }

    ensureChatNotificationPermission();

    const savedUuid = localStorage.getItem(storageKey('conv'));
    if (!savedUuid || (!customer.value && !guestKey.value)) return;

    conversation.value = { uuid: savedUuid };
    try {
        await loadMessages();
        startPolling();
    } catch {
        localStorage.removeItem(storageKey('conv'));
        conversation.value = null;
    }
});

onUnmounted(stopPolling);
</script>

<template>
    <div v-if="enabled" class="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-2">
        <div
            v-if="open"
            class="flex max-h-[min(32rem,70vh)] w-[min(100vw-2rem,22rem)] flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl"
        >
            <div class="flex items-center justify-between border-b border-stone-100 px-4 py-3" style="background: var(--menu-primary)">
                <div>
                    <p class="text-sm font-semibold text-white">Chat com o restaurante</p>
                    <p class="text-xs text-white/80">Canal direto com a loja — não é suporte do App Cardápio</p>
                </div>
                <button
                    type="button"
                    class="rounded-full p-1 text-white/90 hover:bg-white/10 hover:text-white"
                    aria-label="Fechar chat"
                    @click="open = false"
                >
                    <NavIcon name="x" size="sm" />
                </button>
            </div>

            <div v-if="needsGuestInfo && !customer" class="space-y-3 p-4">
                <p class="text-sm text-stone-600">Informe seu nome para iniciar o chat.</p>
                <input v-model="guestName" class="w-full rounded-xl border border-stone-200 px-3 py-2 text-sm" placeholder="Seu nome" />
                <input v-model="guestPhone" class="w-full rounded-xl border border-stone-200 px-3 py-2 text-sm" placeholder="WhatsApp (opcional)" />
                <button
                    type="button"
                    class="w-full rounded-xl py-2.5 text-sm font-semibold text-white disabled:opacity-50"
                    style="background: var(--menu-primary)"
                    :disabled="!guestName.trim() || loading"
                    @click="startChat"
                >
                    Iniciar conversa
                </button>
            </div>

            <template v-else>
                <div ref="messagesEl" class="flex-1 space-y-2 overflow-y-auto p-3">
                    <p v-if="loading" class="text-center text-sm text-stone-400">Conectando…</p>
                    <div
                        v-for="msg in messages"
                        :key="msg.id"
                        class="flex"
                        :class="msg.sender_type === 'customer' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[85%] rounded-2xl px-3 py-2 text-sm"
                            :class="
                                msg.sender_type === 'customer'
                                    ? 'rounded-br-md text-white'
                                    : 'rounded-bl-md bg-stone-100 text-stone-800'
                            "
                            :style="msg.sender_type === 'customer' ? { background: 'var(--menu-primary)' } : {}"
                        >
                            {{ msg.body }}
                        </div>
                    </div>
                </div>
                <form class="flex gap-2 border-t border-stone-100 p-3" @submit.prevent="send">
                    <input
                        v-model="draft"
                        type="text"
                        class="min-w-0 flex-1 rounded-xl border border-stone-200 px-3 py-2 text-sm"
                        placeholder="Digite sua mensagem…"
                        :disabled="conversation?.status !== 'open'"
                    />
                    <button
                        type="submit"
                        class="shrink-0 rounded-xl px-3 py-2 text-sm font-semibold text-white disabled:opacity-50"
                        style="background: var(--menu-primary)"
                        :disabled="sending || !draft.trim()"
                    >
                        Enviar
                    </button>
                </form>
            </template>
        </div>

        <button
            type="button"
            class="relative flex h-14 w-14 items-center justify-center rounded-full text-white shadow-lg transition hover:scale-105"
            style="background: var(--menu-primary)"
            :aria-label="unreadCount > 0 ? `Abrir chat (${unreadCount} novas)` : 'Abrir chat'"
            @click="toggle"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                />
            </svg>
            <span
                v-if="unreadCount > 0"
                class="absolute -right-0.5 -top-0.5 flex min-h-[1.25rem] min-w-[1.25rem] items-center justify-center rounded-full border-2 border-white bg-red-500 px-1 text-[10px] font-bold leading-none text-white"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>
    </div>
</template>
