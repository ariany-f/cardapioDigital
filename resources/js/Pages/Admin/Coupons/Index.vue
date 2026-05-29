<script setup>
import AdminListSearch from '@/Components/Admin/AdminListSearch.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ coupons: Array, branches: Array });

const page = usePage();
const tenant = page.props.tenant;
const editingId = ref(null);

const form = useForm({
    code: '',
    type: 'percent',
    value: '',
    branch_id: '',
    min_order_amount: '',
    max_uses: '',
    is_active: true,
});

const editForm = useForm({
    code: '',
    type: 'percent',
    value: '',
    branch_id: '',
    min_order_amount: '',
    max_uses: '',
    is_active: true,
});

const submit = () =>
    form.post(route('tenant.admin.coupons.store', { tenant: tenant.slug }), {
        onSuccess: () => form.reset(),
    });

const startEdit = (c) => {
    editingId.value = c.id;
    editForm.code = c.code;
    editForm.type = c.type;
    editForm.value = c.value;
    editForm.branch_id = c.branch_id ?? '';
    editForm.min_order_amount = c.min_order_amount ?? '';
    editForm.max_uses = c.max_uses ?? '';
    editForm.is_active = c.is_active;
};

const submitEdit = (id) =>
    editForm.put(route('tenant.admin.coupons.update', { tenant: tenant.slug, coupon: id }), {
        onSuccess: () => (editingId.value = null),
    });

const remove = (id) => router.delete(route('tenant.admin.coupons.destroy', { tenant: tenant.slug, coupon: id }));
</script>

<template>
    <Head title="Cupons" />

    <h1 class="admin-page-title">Cupons de desconto</h1>

    <form @submit.prevent="submit" class="admin-card mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <input v-model="form.code" placeholder="Código" class="admin-input uppercase" required />
        <select v-model="form.type" class="admin-input">
            <option value="percent">Percentual (%)</option>
            <option value="fixed">Valor fixo (R$)</option>
        </select>
        <input v-model="form.value" type="number" step="0.01" min="0" placeholder="Valor" class="admin-input" required />
        <select v-model="form.branch_id" class="admin-input">
            <option value="">Todas as filiais</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
        <input v-model="form.min_order_amount" type="number" step="0.01" placeholder="Pedido mínimo (opcional)" class="admin-input" />
        <input v-model="form.max_uses" type="number" min="1" placeholder="Máx. usos (opcional)" class="admin-input" />
        <button type="submit" class="admin-btn-primary sm:col-span-2 lg:col-span-3">Criar cupom</button>
    </form>

    <AdminListSearch
        :href="route('tenant.admin.coupons.index', { tenant: tenant.slug })"
        :filters="filters"
        placeholder="Buscar cupom..."
    />

    <div class="admin-table-wrap mt-6">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Filial</th>
                    <th>Usos</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="c in coupons" :key="c.id">
                    <td class="font-mono font-medium">{{ c.code }}</td>
                    <td>{{ c.type === 'percent' ? '%' : 'R$' }}</td>
                    <td>{{ c.type === 'percent' ? c.value + '%' : 'R$ ' + parseFloat(c.value).toFixed(2) }}</td>
                    <td>{{ c.branch?.name ?? 'Todas' }}</td>
                    <td>{{ c.uses_count }}{{ c.max_uses ? ' / ' + c.max_uses : '' }}</td>
                    <td class="text-right">
                        <button type="button" class="text-orange-600" @click="startEdit(c)">Editar</button>
                        <button type="button" class="ml-2 text-red-600" @click="remove(c.id)">Excluir</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <form v-if="editingId" @submit.prevent="submitEdit(editingId)" class="admin-card fixed inset-x-4 bottom-4 z-50 mx-auto max-w-lg shadow-xl">
        <h2 class="font-semibold">Editar cupom</h2>
        <div class="mt-3 grid gap-2">
            <input v-model="editForm.code" class="admin-input uppercase" />
            <select v-model="editForm.type" class="admin-input">
                <option value="percent">Percentual</option>
                <option value="fixed">Fixo</option>
            </select>
            <input v-model="editForm.value" type="number" step="0.01" class="admin-input" />
            <label class="flex items-center gap-2 text-sm"><input v-model="editForm.is_active" type="checkbox" /> Ativo</label>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="submit" class="admin-btn-primary">Salvar</button>
            <button type="button" class="admin-btn-secondary" @click="editingId = null">Cancelar</button>
        </div>
    </form>
</template>
