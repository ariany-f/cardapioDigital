<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const page = usePage();
const tenant = page.props.tenant;

const from = ref('');
const to = ref('');

const downloadUrl = () => {
    const params = new URLSearchParams();
    if (from.value) params.set('from', from.value);
    if (to.value) params.set('to', to.value);
    const qs = params.toString();
    return route('tenant.admin.reports.orders', { tenant: tenant.slug }) + (qs ? `?${qs}` : '');
};
</script>

<template>
    <Head title="Relatórios" />

    <h1 class="admin-page-title">Relatórios</h1>
    <p class="mt-1 text-sm text-stone-500">Exporte pedidos em CSV para planilhas.</p>

    <div class="admin-card mt-6 max-w-md">
        <h2 class="font-semibold">Pedidos</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div>
                <label class="admin-stat-label mb-1 block text-xs font-medium">De</label>
                <input v-model="from" type="date" class="admin-input" />
            </div>
            <div>
                <label class="admin-stat-label mb-1 block text-xs font-medium">Até</label>
                <input v-model="to" type="date" class="admin-input" />
            </div>
        </div>
        <a :href="downloadUrl()" class="admin-btn-primary mt-4 inline-block">
            Baixar CSV
        </a>
    </div>
</template>
