<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

defineProps({ reports: Array });

const page = usePage();
const tenant = page.props.tenant;
const editingId = ref(null);

const form = useForm({
    status: 'reviewed',
    admin_response: '',
    deactivate_motoboy: false,
});

const startEdit = (r) => {
    editingId.value = r.id;
    form.status = r.status;
    form.admin_response = r.admin_response ?? '';
    form.deactivate_motoboy = false;
};

const submit = (id) =>
    form.patch(route('tenant.admin.motoboy-reports.update', { tenant: tenant.slug, report: id }), {
        onSuccess: () => {
            editingId.value = null;
            form.reset();
        },
    });
</script>

<template>
    <Head title="Denúncias de entregadores" />
    <h1 class="admin-page-title">Denúncias de entregadores</h1>
    <p class="mt-1 text-sm text-stone-500">Relatos de clientes sobre entregadores.</p>

    <ul class="mt-6 space-y-4">
        <li v-for="r in reports" :key="r.id" class="admin-card">
            <div class="flex justify-between gap-2">
                <div>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">{{ r.status }}</span>
                    <p class="mt-2 font-semibold">{{ r.motoboy?.name }}</p>
                    <p class="text-sm text-stone-500">Pedido {{ r.order?.order_number }} · {{ r.customer?.name || 'Cliente' }}</p>
                    <p class="mt-2 text-sm">{{ r.message }}</p>
                </div>
                <button v-if="editingId !== r.id" type="button" class="text-sm text-orange-600" @click="startEdit(r)">Tratar</button>
            </div>
            <form v-if="editingId === r.id" class="mt-4 space-y-3 border-t pt-4" @submit.prevent="submit(r.id)">
                <select v-model="form.status" class="admin-input">
                    <option value="open">Aberta</option>
                    <option value="reviewed">Tratada</option>
                    <option value="dismissed">Arquivada</option>
                </select>
                <textarea v-model="form.admin_response" class="admin-input" rows="3" placeholder="Mensagem ao cliente (registro interno)" />
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.deactivate_motoboy" type="checkbox" />
                    Desativar entregador
                </label>
                <button type="submit" class="admin-btn-primary">Salvar</button>
            </form>
        </li>
    </ul>
    <p v-if="!reports?.length" class="mt-8 text-center text-stone-500">Nenhuma denúncia.</p>
</template>
