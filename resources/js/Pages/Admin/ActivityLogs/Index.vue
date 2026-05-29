<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    logs: Object,
    filters: Object,
    actionOptions: Array,
    subjectOptions: Array,
});

const page = usePage();
const tenant = page.props.tenant;

const subjectLabel = (type) =>
    ({ Order: 'Pedido', SupportRequest: 'Suporte', Branch: 'Filial' }[type] ?? type);

const applyFilters = (patch) => {
    router.get(
        route('tenant.admin.activity-logs.index', { tenant: tenant.slug }),
        { ...props.filters, ...patch },
        { preserveState: true },
    );
};
</script>

<template>
    <Head title="Registro de atividades" />

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="admin-page-title">Registro de atividades</h1>
            <p class="mt-1 text-sm text-stone-500">
                Histórico de ações: aprovações, cancelamentos, suporte e alterações.
            </p>
        </div>
    </div>

    <div class="admin-card mt-6 flex flex-wrap gap-3">
        <input
            type="search"
            class="admin-input max-w-xs"
            placeholder="Buscar..."
            :value="filters?.q ?? ''"
            @change="applyFilters({ q: $event.target.value || undefined })"
        />
        <select
            class="admin-input w-auto min-w-[160px]"
            :value="filters?.subject ?? ''"
            @change="applyFilters({ subject: $event.target.value || undefined })"
        >
            <option v-for="opt in subjectOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <select
            class="admin-input w-auto min-w-[200px]"
            :value="filters?.action ?? ''"
            @change="applyFilters({ action: $event.target.value || undefined })"
        >
            <option value="">Todas as ações</option>
            <option v-for="opt in actionOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
            </option>
        </select>
        <input
            type="date"
            class="admin-input w-auto"
            :value="filters?.date_from ?? ''"
            @change="applyFilters({ date_from: $event.target.value || undefined })"
        />
        <input
            type="date"
            class="admin-input w-auto"
            :value="filters?.date_to ?? ''"
            @change="applyFilters({ date_to: $event.target.value || undefined })"
        />
    </div>

    <div class="admin-table-wrap mt-6">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Data/hora</th>
                    <th>Ação</th>
                    <th>Descrição</th>
                    <th>Responsável</th>
                    <th>Referência</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="log in logs.data" :key="log.id">
                    <td class="whitespace-nowrap text-sm text-stone-600">{{ log.created_at }}</td>
                    <td>
                        <span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-700">
                            {{ log.action_label }}
                        </span>
                    </td>
                    <td class="text-sm">{{ log.description }}</td>
                    <td class="text-sm">
                        <span class="font-medium">{{ log.actor_name }}</span>
                        <span class="text-stone-400"> · {{ log.origin }}</span>
                    </td>
                    <td class="text-sm text-stone-600">
                        {{ subjectLabel(log.subject_type) }} #{{ log.subject_id }}
                        <Link
                            v-if="log.subject_type === 'Order'"
                            :href="route('tenant.admin.orders.show', { tenant: tenant.slug, order: log.subject_id })"
                            class="ml-1 text-orange-600 hover:underline"
                        >
                            Ver
                        </Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <p v-if="!logs.data?.length" class="mt-6 text-center text-sm text-stone-500">Nenhum registro encontrado.</p>

    <div v-if="logs.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link
            v-for="link in logs.links"
            :key="link.label"
            :href="link.url || '#'"
            class="rounded px-3 py-1 text-sm"
            :class="link.active ? 'bg-orange-600 text-white' : 'bg-stone-100 text-stone-700'"
            v-html="link.label"
        />
    </div>
</template>
