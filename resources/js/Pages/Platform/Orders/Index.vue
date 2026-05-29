<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PlatformLayout });

const props = defineProps({ orders: Object, tenants: Array, filters: Object });

const apply = (patch) =>
    router.get(route('platform.orders.index'), { ...props.filters, ...patch }, { preserveState: true });
</script>

<template>
    <Head title="Pedidos — Plataforma" />
    <h1 class="text-2xl font-bold text-slate-900">Pedidos (todos os restaurantes)</h1>
    <p class="mt-1 max-w-3xl text-sm text-slate-500">
        Visão global para suporte e auditoria. A operação de cada pedido (preparo, entrega, pagamento e atendimento ao cliente)
        é de responsabilidade do restaurante — use o painel do estabelecimento para alterar status ou resolver problemas.
    </p>

    <div class="mt-4 flex flex-wrap gap-2">
        <input
            type="search"
            class="admin-input max-w-xs"
            placeholder="Nº pedido, cliente..."
            :value="filters?.q ?? ''"
            @change="apply({ q: $event.target.value || undefined })"
        />
        <select class="admin-input w-auto" :value="filters?.tenant_id ?? ''" @change="apply({ tenant_id: $event.target.value || undefined })">
            <option value="">Todos os restaurantes</option>
            <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
        <select class="admin-input w-auto" :value="filters?.status ?? ''" @change="apply({ status: $event.target.value || undefined })">
            <option value="">Todos os status</option>
            <option value="pending_approval">Aguardando</option>
            <option value="confirmed">Confirmado</option>
            <option value="preparing">Preparo</option>
            <option value="delivered">Entregue</option>
            <option value="cancelled">Cancelado</option>
        </select>
    </div>

    <div class="admin-table-wrap mt-6">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Restaurante</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Pagamento</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="o in orders.data" :key="o.id">
                    <td>
                        <Link
                            :href="`/${o.tenant?.slug}/admin/orders/${o.id}`"
                            class="font-medium text-indigo-600 hover:underline"
                        >
                            {{ o.order_number }}
                        </Link>
                    </td>
                    <td>{{ o.tenant?.name }} · {{ o.branch?.name }}</td>
                    <td>{{ o.guest_name }}</td>
                    <td>R$ {{ parseFloat(o.total).toFixed(2) }}</td>
                    <td>{{ o.status }}</td>
                    <td>{{ o.payment_status }} ({{ o.payment_method }})</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="orders.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link
            v-for="link in orders.links"
            :key="link.label"
            :href="link.url || '#'"
            class="rounded px-3 py-1 text-sm"
            :class="link.active ? 'bg-indigo-600 text-white' : 'bg-stone-100 text-stone-700'"
            v-html="link.label"
        />
    </div>
</template>
