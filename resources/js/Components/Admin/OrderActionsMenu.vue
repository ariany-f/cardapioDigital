<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    canManage: { type: Boolean, default: false },
    paymentPaid: { type: Boolean, default: false },
    showEditDelivery: { type: Boolean, default: false },
});

const emit = defineEmits(['revert-payment', 'correct-status', 'edit-delivery']);

const open = ref(false);
const root = ref(null);

const hasItems = computed(() => props.canManage && (props.paymentPaid || true));

const close = () => {
    open.value = false;
};

const onRevertPayment = () => {
    close();
    emit('revert-payment');
};

const onCorrectStatus = () => {
    close();
    emit('correct-status');
};

const onEditDelivery = () => {
    close();
    emit('edit-delivery');
};

const onDocumentClick = (event) => {
    if (!open.value || !root.value?.contains(event.target)) {
        close();
    }
};

onMounted(() => document.addEventListener('click', onDocumentClick));
onUnmounted(() => document.removeEventListener('click', onDocumentClick));
</script>

<template>
    <div v-if="canManage && hasItems" ref="root" class="relative">
        <button
            type="button"
            class="admin-btn-secondary inline-flex items-center gap-1 text-sm"
            aria-haspopup="menu"
            :aria-expanded="open"
            @click.stop="open = !open"
        >
            Ações
            <span class="text-xs opacity-60" aria-hidden="true">▾</span>
        </button>
        <div
            v-if="open"
            class="absolute right-0 z-30 mt-1 min-w-[14rem] overflow-hidden rounded-xl border border-stone-200 bg-white py-1 shadow-lg"
            role="menu"
            @click.stop
        >
            <button
                v-if="paymentPaid"
                type="button"
                class="block w-full px-3 py-2 text-left text-sm text-stone-700 hover:bg-stone-50"
                role="menuitem"
                @click="onRevertPayment"
            >
                Desfazer confirmação de pagamento
            </button>
            <button
                v-if="showEditDelivery"
                type="button"
                class="block w-full px-3 py-2 text-left text-sm text-stone-700 hover:bg-stone-50"
                role="menuitem"
                @click="onEditDelivery"
            >
                Editar entrega
            </button>
            <button
                type="button"
                class="block w-full px-3 py-2 text-left text-sm text-stone-700 hover:bg-stone-50"
                role="menuitem"
                @click="onCorrectStatus"
            >
                Corrigir status do pedido
            </button>
        </div>
    </div>
</template>
