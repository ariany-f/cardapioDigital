<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { playNewOrderSound } from '@/composables/useOrderAlertSound';
import { usePermissions } from '@/composables/usePermissions';
import { Head, router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, watch } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ orders: Array });

const page = usePage();
const tenant = page.props.tenant;
const { can } = usePermissions();
const soundEnabled = ref(true);
const knownOrderIds = ref(new Set());

let refreshTimer;

const trackOrders = (list) => {
    const ids = new Set((list ?? []).map((o) => o.id));
    if (knownOrderIds.value.size > 0) {
        const hasNew = [...ids].some((id) => !knownOrderIds.value.has(id));
        if (hasNew && soundEnabled.value) {
            playNewOrderSound();
        }
    }
    knownOrderIds.value = ids;
};

watch(() => props.orders, trackOrders, { immediate: true });

onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({ only: ['orders'], preserveScroll: true, preserveState: true });
    }, 15000);
});

onUnmounted(() => clearInterval(refreshTimer));

const statusLabel = (status) => page.props.translations?.[`order.status.${status}`] ?? status;

const advance = (order) => {
    const next = {
        pending_approval: 'confirmed',
        confirmed: 'preparing',
        preparing: 'ready',
        ready: order.type === 'delivery' ? 'out_for_delivery' : 'delivered',
    };
    const status = next[order.status];
    if (status) {
        router.patch(route('tenant.admin.orders.status', { tenant: tenant.slug, order: order.id }), { status });
    }
};

const accept = (order) => {
    router.post(route('tenant.admin.orders.accept', { tenant: tenant.slug, order: order.id }));
};

const rejectOrder = (order) => {
    const reason = window.prompt('Motivo da recusa (opcional):') ?? '';
    router.post(route('tenant.admin.orders.reject', { tenant: tenant.slug, order: order.id }), {
        cancel_reason: reason,
    });
};
</script>

<template>
    <Head title="KDS" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="admin-page-title">Cozinha (KDS)</h1>
        <div class="flex items-center gap-4 text-sm">
            <label class="flex cursor-pointer items-center gap-2 text-stone-500">
                <input v-model="soundEnabled" type="checkbox" class="rounded border-stone-300" />
                Som ao chegar pedido
            </label>
            <span class="text-xs text-stone-500">Atualiza a cada 15s</span>
        </div>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <article
            v-for="order in orders"
            :key="order.id"
            class="admin-card border-2"
            :class="order.status === 'pending_approval' ? 'border-amber-400' : 'border-orange-200'"
        >
            <div class="flex items-center justify-between">
                <span class="font-bold">{{ order.order_number }}</span>
                <span class="text-xs text-stone-500">{{ order.branch?.name }}</span>
            </div>
            <p v-if="order.table?.name" class="mt-0.5 text-xs font-medium text-orange-700">Mesa {{ order.table.name }}</p>
            <p class="mt-1 text-sm">{{ statusLabel(order.status) }}</p>
            <ul class="mt-3 space-y-1 text-sm">
                <li v-for="item in order.items" :key="item.id">
                    <strong>{{ item.quantity }}x</strong> {{ item.name }}
                </li>
            </ul>
            <div v-if="order.status === 'pending_approval'" class="mt-4 flex gap-2">
                <button
                    v-if="can('orders.accept')"
                    type="button"
                    class="flex-1 rounded-xl bg-green-600 py-2 text-sm font-semibold text-white hover:bg-green-700"
                    @click="accept(order)"
                >
                    Aprovar
                </button>
                <button
                    v-if="can('orders.cancel')"
                    type="button"
                    class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
                    @click="rejectOrder(order)"
                >
                    Recusar
                </button>
            </div>
            <button
                v-else-if="['confirmed', 'preparing', 'ready'].includes(order.status)"
                type="button"
                class="admin-btn-primary mt-4 w-full"
                @click="advance(order)"
            >
                Avançar
            </button>
        </article>
    </div>

    <p v-if="!orders?.length" class="mt-8 text-center text-stone-500">Nenhum pedido na cozinha.</p>
</template>
