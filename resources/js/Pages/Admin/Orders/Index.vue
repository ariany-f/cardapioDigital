<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useAdminOrdersPending } from '@/composables/useAdminOrdersPending';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

defineProps({ orders: Object, filters: Object });

const page = usePage();
const tenant = page.props.tenant;
const { can } = usePermissions();
const { refreshPending } = useAdminOrdersPending();

const statusLabel = (status) => page.props.translations?.[`order.status.${status}`] ?? status;

const filterStatus = (status) => {
    router.get(route('tenant.admin.orders.index', { tenant: tenant.slug }), { status: status || undefined }, { preserveState: true });
};

const acceptOrder = (order) => {
    if (!confirm(`Aprovar pedido ${order.order_number}?`)) return;
    router.post(route('tenant.admin.orders.accept', { tenant: tenant.slug, order: order.id }), {
        onSuccess: () => refreshPending(),
    });
};

const statusBadgeClass = (status) => {
    if (status === 'pending_approval') return 'bg-amber-100 text-amber-900';
    if (status === 'cancelled' || status === 'rejected') return 'bg-stone-200 text-stone-700';
    if (status === 'preparing') return 'bg-blue-100 text-blue-900';
    if (status === 'ready') return 'bg-purple-100 text-purple-900';
    return 'bg-orange-50 text-orange-900';
};
</script>

<template>
    <Head title="Pedidos" />

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="admin-page-title">Pedidos</h1>
        <div class="flex flex-wrap items-center gap-2">
            <Link
                v-if="can('orders.accept')"
                :href="route('tenant.admin.orders.settings', { tenant: tenant.slug })"
                class="admin-btn-secondary text-sm"
            >
                Aprovação automática
            </Link>
        </div>
        <div class="flex w-full flex-wrap gap-2 sm:w-auto">
            <button class="admin-btn-secondary px-3 py-1 text-sm" @click="filterStatus('')">Todos</button>
            <button class="admin-btn-secondary px-3 py-1 text-sm" @click="filterStatus('pending_approval')">Aguardando</button>
            <button class="admin-btn-secondary px-3 py-1 text-sm" @click="filterStatus('confirmed')">Confirmados</button>
            <button class="admin-btn-secondary px-3 py-1 text-sm" @click="filterStatus('preparing')">Preparo</button>
            <button class="admin-btn-secondary px-3 py-1 text-sm" @click="filterStatus('cancelled')">Cancelados</button>
        </div>
    </div>

    <div class="admin-table-wrap mt-6">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Cliente</th>
                    <th>Filial</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="order in orders.data" :key="order.id">
                    <td class="font-medium">{{ order.order_number }}</td>
                    <td class="text-sm text-stone-600">{{ order.guest_name }}</td>
                    <td>{{ order.branch?.name }}</td>
                    <td>
                        <span
                            class="inline-block rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="statusBadgeClass(order.status)"
                        >
                            {{ statusLabel(order.status) }}
                        </span>
                    </td>
                    <td>R$ {{ parseFloat(order.total).toFixed(2) }}</td>
                    <td class="text-right">
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <button
                                v-if="order.status === 'pending_approval' && can('orders.accept')"
                                type="button"
                                class="text-sm font-semibold text-green-700 hover:underline"
                                @click="acceptOrder(order)"
                            >
                                Aprovar
                            </button>
                            <Link
                                :href="route('tenant.admin.orders.show', { tenant: tenant.slug, order: order.id })"
                                class="text-sm text-orange-600 hover:underline"
                            >
                                Ver
                            </Link>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
