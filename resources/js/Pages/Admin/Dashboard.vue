<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

defineProps({
    stats: Object,
    pendingOrders: Array,
    recentOrders: Array,
    tenantName: String,
    branches: Array,
    posMode: Boolean,
    requestsMode: Boolean,
    languagesMode: Boolean,
    products: Array,
    requests: Array,
    languages: Array,
});

const page = usePage();
const tenant = page.props.tenant;
const { can } = usePermissions();

const statusLabel = (status) => page.props.translations?.[`order.status.${status}`] ?? status;

const setOrdersStatus = (branchId, override) => {
    router.patch(
        route('tenant.admin.branches.orders-status', { tenant: tenant.slug, branch: branchId }),
        { orders_status_override: override },
        { preserveScroll: true },
    );
};

const setAutoAccept = (branchId, enabled) => {
    router.patch(
        route('tenant.admin.branches.orders-status', { tenant: tenant.slug, branch: branchId }),
        { auto_accept_orders: enabled },
        { preserveScroll: true },
    );
};

const acceptOrder = (order) => {
    if (!confirm(`Aprovar pedido ${order.order_number}?`)) return;
    router.post(route('tenant.admin.orders.accept', { tenant: tenant.slug, order: order.id }));
};

const statusBadgeClass = (branch) => {
    if (branch.orders_status_override === 'open') return 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200';
    if (branch.orders_status_override === 'closed') return 'bg-red-100 text-red-800 ring-1 ring-red-200';
    return branch.is_open_by_schedule
        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
        : 'bg-stone-100 text-stone-600 ring-1 ring-stone-200';
};
</script>

<template>
    <Head title="Painel Admin" />

    <h1 class="admin-page-title">
        {{ posMode ? 'PDV' : requestsMode ? 'Solicitações' : languagesMode ? 'Idiomas' : 'Painel' }}
        <span class="font-normal text-stone-500">— {{ tenantName }}</span>
    </h1>

    <template v-if="!posMode && !requestsMode && !languagesMode">
        <section v-if="branches?.length" class="admin-card mt-6">
            <h2 class="font-semibold text-stone-900">Cardápio e pedidos</h2>
            <p class="mt-1 text-sm text-stone-500">
                Abra ou feche manualmente, independente do horário cadastrado na filial.
            </p>

            <ul class="mt-4 space-y-3">
                <li
                    v-for="branch in branches"
                    :key="branch.id"
                    class="rounded-xl border border-stone-200 bg-stone-50 p-4"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold text-stone-900">{{ branch.name }}</p>
                            <span
                                class="mt-1 inline-block rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="statusBadgeClass(branch)"
                            >
                                {{ branch.status_label }}
                            </span>
                            <p class="mt-1 text-xs text-stone-500">
                                Clientes {{ branch.accepting_orders ? 'podem' : 'não podem' }} fazer pedidos agora.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="admin-btn-primary text-sm"
                                :class="{ 'ring-2 ring-orange-300': branch.orders_status_override === 'open' }"
                                @click="setOrdersStatus(branch.id, 'open')"
                            >
                                Abrir agora
                            </button>
                            <button
                                type="button"
                                class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
                                :class="{ 'ring-2 ring-red-300': branch.orders_status_override === 'closed' }"
                                @click="setOrdersStatus(branch.id, 'closed')"
                            >
                                Fechar agora
                            </button>
                            <button
                                type="button"
                                class="admin-btn-secondary text-sm"
                                :class="{ 'ring-2 ring-stone-300': !branch.orders_status_override }"
                                @click="setOrdersStatus(branch.id, null)"
                            >
                                Seguir horário
                            </button>
                        </div>
                    </div>
                    <label
                        v-if="can('orders.accept')"
                        class="mt-3 flex cursor-pointer items-start gap-2 border-t border-stone-200 pt-3 text-sm text-stone-700"
                    >
                        <input
                            type="checkbox"
                            class="mt-0.5 rounded border-stone-300"
                            :checked="branch.auto_accept_orders"
                            @change="setAutoAccept(branch.id, $event.target.checked)"
                        />
                        <span>
                            <strong>Aprovação automática</strong> — novos pedidos do cardápio entram como confirmados,
                            sem passar por “aguardando confirmação”.
                        </span>
                    </label>
                </li>
            </ul>
        </section>

        <section v-if="pendingOrders?.length" class="admin-card mt-6 border-2 border-amber-200">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-amber-900">Aguardando aprovação</h2>
                    <p class="text-sm text-stone-500">{{ pendingOrders.length }} pedido(s) precisam de confirmação.</p>
                </div>
                <Link
                    :href="route('tenant.admin.orders.index', { tenant: tenant.slug, status: 'pending_approval' })"
                    class="text-sm font-medium text-orange-600 hover:underline"
                >
                    Ver todos
                </Link>
            </div>
            <ul class="divide-y divide-amber-100">
                <li
                    v-for="order in pendingOrders"
                    :key="order.id"
                    class="flex flex-wrap items-center justify-between gap-3 py-3"
                >
                    <div>
                        <p class="font-medium">{{ order.order_number }}</p>
                        <p class="text-sm text-stone-500">
                            {{ order.guest_name }} · {{ order.branch?.name }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-semibold">R$ {{ parseFloat(order.total).toFixed(2) }}</span>
                        <button
                            v-if="can('orders.accept')"
                            type="button"
                            class="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-green-700"
                            @click="acceptOrder(order)"
                        >
                            Aprovar
                        </button>
                        <Link
                            :href="route('tenant.admin.orders.show', { tenant: tenant.slug, order: order.id })"
                            class="text-sm text-orange-600 hover:underline"
                        >
                            Ver pedido
                        </Link>
                    </div>
                </li>
            </ul>
        </section>

        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="admin-card">
                <p class="admin-stat-label">Pedidos hoje</p>
                <p class="text-2xl font-bold">{{ stats.orders_today }}</p>
            </div>
            <div class="admin-card">
                <p class="admin-stat-label">Aguardando</p>
                <p class="text-2xl font-bold text-amber-600">{{ stats.orders_pending }}</p>
            </div>
            <div class="admin-card">
                <p class="admin-stat-label">Em preparo</p>
                <p class="text-2xl font-bold text-blue-600">{{ stats.orders_preparing }}</p>
            </div>
            <div class="admin-card">
                <p class="admin-stat-label">Faturamento hoje</p>
                <p class="text-2xl font-bold text-green-600">R$ {{ parseFloat(stats.revenue_today || 0).toFixed(2) }}</p>
            </div>
        </div>

        <div class="admin-card mt-8">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold">Pedidos recentes</h2>
                <Link :href="route('tenant.admin.orders.index', { tenant: tenant.slug })" class="text-sm text-orange-600">
                    Ver todos
                </Link>
            </div>
            <ul class="divide-y divide-gray-100">
                <li v-for="order in recentOrders" :key="order.id" class="flex items-center justify-between py-3">
                    <div>
                        <p class="font-medium">{{ order.order_number }}</p>
                        <p class="text-sm text-stone-500">{{ order.branch?.name }}</p>
                    </div>
                    <div class="flex items-center gap-4 text-right">
                        <div>
                            <p class="text-sm">{{ statusLabel(order.status) }}</p>
                            <p class="font-semibold">R$ {{ parseFloat(order.total).toFixed(2) }}</p>
                        </div>
                        <Link
                            :href="route('tenant.admin.orders.show', { tenant: tenant.slug, order: order.id })"
                            class="text-sm text-orange-600 hover:underline"
                        >
                            Ver
                        </Link>
                    </div>
                </li>
            </ul>
        </div>
    </template>

    <div v-if="posMode && products?.length" class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div v-for="p in products" :key="p.id" class="admin-card text-center">
            <p class="font-medium">{{ p.name }}</p>
            <p class="text-orange-600">R$ {{ parseFloat(p.base_price).toFixed(2) }}</p>
        </div>
    </div>

    <ul v-if="requestsMode && requests?.length" class="mt-6 space-y-3">
        <li v-for="r in requests" :key="r.id" class="admin-card">
            <p class="font-medium">{{ r.subject }}</p>
            <p class="text-sm text-stone-500">{{ r.message }}</p>
            <span class="mt-2 inline-block text-xs text-stone-500">{{ r.status }}</span>
        </li>
    </ul>

    <ul v-if="languagesMode && languages?.length" class="mt-6 space-y-2">
        <li v-for="lang in languages" :key="lang.id" class="admin-card py-3">
            {{ lang.flag }} {{ lang.name }} ({{ lang.code }})
        </li>
    </ul>
</template>
