<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

defineProps({
    tables: Array,
    branches: Array,
});

const page = usePage();
const tenant = page.props.tenant;

const form = useForm({
    branch_id: '',
    name: '',
});

const submit = () =>
    form.post(route('tenant.admin.tables.store', { tenant: tenant.slug }), {
        onSuccess: () => form.reset('name'),
    });

const remove = (id) => {
    if (confirm('Remover esta mesa?')) {
        router.delete(route('tenant.admin.tables.destroy', { tenant: tenant.slug, table: id }));
    }
};

const copy = (url) => navigator.clipboard.writeText(url);
</script>

<template>
    <Head title="Mesas e QR" />

    <h1 class="admin-page-title">Mesas e QR</h1>
    <p class="mt-1 text-sm text-stone-500">Cada mesa tem um link para o cliente pedir pelo celular.</p>

    <form class="admin-card mt-6 flex flex-wrap items-end gap-3" @submit.prevent="submit">
        <div>
            <label class="mb-1 block text-sm font-medium text-stone-700">Filial</label>
            <select v-model="form.branch_id" class="admin-input" required>
                <option value="" disabled>Selecione</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-stone-700">Nome da mesa</label>
            <input v-model="form.name" class="admin-input" placeholder="Mesa 01" required />
        </div>
        <button type="submit" class="admin-btn-primary" :disabled="form.processing">
            Adicionar mesa
        </button>
    </form>

    <div class="admin-table-wrap mt-6">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mesa</th>
                    <th>Filial</th>
                    <th>Link do cardápio</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="t in tables" :key="t.id">
                    <td class="font-medium">{{ t.name }}</td>
                    <td>{{ t.branch?.name }}</td>
                    <td>
                        <span class="font-mono text-xs text-stone-500">{{ t.menu_url }}</span>
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <button type="button" class="text-orange-600 hover:underline" @click="copy(t.menu_url)">Copiar</button>
                        <button type="button" class="ml-3 text-red-600 hover:underline" @click="remove(t.id)">Excluir</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <p v-if="!tables?.length" class="px-4 py-8 text-center text-stone-500">Nenhuma mesa cadastrada.</p>
    </div>
</template>
