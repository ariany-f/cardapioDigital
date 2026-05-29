<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    leads: Object,
    filters: Object,
    statusLabels: Object,
    pendingCount: Number,
});

const apply = (patch) =>
    router.get(route('platform.marketing-leads.index'), { ...props.filters, ...patch }, { preserveState: true });

const formatDate = (iso) =>
    iso
        ? new Date(iso).toLocaleString('pt-BR', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '—';

const statusClass = (status) => {
    if (status === 'pending') return 'bg-amber-100 text-amber-900';
    if (status === 'contacted') return 'bg-blue-100 text-blue-900';
    return 'bg-slate-100 text-slate-600';
};
</script>

<template>
    <Head title="Solicitações — Plataforma" />

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Solicitações da landing</h1>
            <p class="mt-1 text-sm text-slate-500">
                Contatos do formulário da
                <a :href="route('marketing.landing')" target="_blank" class="text-indigo-600 hover:underline">landing</a>
                (interesse em contratar a plataforma) — não são pedidos de clientes finais nos restaurantes.
            </p>
        </div>
        <span
            v-if="pendingCount > 0"
            class="rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-900"
        >
            {{ pendingCount }} nova{{ pendingCount === 1 ? '' : 's' }}
        </span>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <input
            type="search"
            class="platform-input max-w-xs"
            placeholder="Restaurante, nome, e-mail, cidade…"
            :value="filters?.q ?? ''"
            @change="apply({ q: $event.target.value || undefined })"
        />
        <select
            class="platform-input w-auto"
            :value="filters?.status ?? ''"
            @change="apply({ status: $event.target.value || undefined })"
        >
            <option value="">Todos os status</option>
            <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
        </select>
    </div>

    <div class="mt-6 platform-table-wrap">
        <table class="platform-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Restaurante</th>
                    <th>Contato</th>
                    <th>Cidade</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="!leads.data?.length">
                    <td colspan="6" class="py-8 text-center text-slate-500">Nenhuma solicitação encontrada.</td>
                </tr>
                <tr v-for="lead in leads.data" :key="lead.id">
                    <td class="whitespace-nowrap text-slate-600">{{ formatDate(lead.created_at) }}</td>
                    <td class="font-medium text-slate-900">{{ lead.restaurant_name }}</td>
                    <td>
                        <p>{{ lead.contact_name }}</p>
                        <p class="text-xs text-slate-500">{{ lead.email }}</p>
                        <p v-if="lead.phone" class="text-xs text-slate-500">{{ lead.phone }}</p>
                    </td>
                    <td>{{ lead.city || '—' }}</td>
                    <td>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusClass(lead.status)">
                            {{ statusLabels[lead.status] ?? lead.status }}
                        </span>
                    </td>
                    <td class="text-right">
                        <Link
                            :href="route('platform.marketing-leads.show', lead.id)"
                            class="text-indigo-600 hover:underline"
                        >
                            Ver
                        </Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="leads.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link
            v-for="link in leads.links"
            :key="link.label"
            :href="link.url || '#'"
            class="rounded px-3 py-1 text-sm"
            :class="link.active ? 'bg-indigo-600 text-white' : 'bg-stone-100'"
            v-html="link.label"
        />
    </div>
</template>
