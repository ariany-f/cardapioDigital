<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    payments: Object,
    filters: Object,
    tenants: Array,
    filterTenant: Object,
});

const formatMoney = (value) =>
    parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const formatDate = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const filterByTenant = (tenantId) => {
    router.get(
        route('platform.payments.index'),
        tenantId ? { tenant_id: tenantId } : {},
        { preserveState: true },
    );
};
</script>

<template>
    <Head title="Pagamentos" />

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pagamentos dos restaurantes</h1>
            <p v-if="filterTenant" class="mt-1 text-sm text-slate-500">
                Filtrando: <strong>{{ filterTenant.name }}</strong>
                <button type="button" class="ml-2 text-indigo-600 hover:underline" @click="filterByTenant(null)">
                    Ver todos
                </button>
            </p>
        </div>
        <Link
            :href="route('platform.payments.create', filterTenant ? { tenant_id: filterTenant.id } : {})"
            class="platform-btn-primary"
        >
            Registrar pagamento
        </Link>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <label class="text-sm text-slate-600">Restaurante:</label>
        <select
            class="platform-input w-auto"
            :value="filters.tenant_id ?? ''"
            @change="filterByTenant($event.target.value || null)"
        >
            <option value="">Todos</option>
            <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
    </div>

    <div class="mt-6 platform-table-wrap">
        <table class="platform-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Restaurante</th>
                    <th>Plano</th>
                    <th>Valor</th>
                    <th>Referência</th>
                    <th>Registrado por</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in payments.data" :key="p.id">
                    <td class="whitespace-nowrap text-slate-600">{{ formatDate(p.paid_at) }}</td>
                    <td>
                        <Link
                            :href="route('platform.payments.index', { tenant_id: p.tenant_id })"
                            class="font-medium text-slate-900 hover:text-indigo-600"
                        >
                            {{ p.tenant?.name }}
                        </Link>
                        <span class="block text-xs text-slate-400">/{{ p.tenant?.slug }}</span>
                    </td>
                    <td>{{ p.subscription?.plan?.name ?? '—' }}</td>
                    <td class="font-semibold text-green-700">{{ formatMoney(p.amount) }}</td>
                    <td class="text-slate-600">{{ p.reference || '—' }}</td>
                    <td class="text-slate-600">{{ p.marked_by?.name ?? '—' }}</td>
                    <td class="text-right whitespace-nowrap">
                        <Link
                            :href="route('platform.payments.edit', p.id)"
                            class="text-sm font-medium text-indigo-600 hover:underline"
                        >
                            Editar
                        </Link>
                    </td>
                </tr>
            </tbody>
        </table>

        <p v-if="!payments.data?.length" class="px-4 py-12 text-center text-slate-500">
            Nenhum pagamento registrado.
            <Link :href="route('platform.payments.create')" class="text-indigo-600 hover:underline">Registrar o primeiro</Link>
        </p>
    </div>

    <div v-if="payments.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link
            v-for="link in payments.links"
            :key="link.label"
            :href="link.url || '#'"
            class="rounded border px-3 py-1 text-sm"
            :class="link.active ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600'"
            v-html="link.label"
        />
    </div>
</template>
