<script setup>
import AdminListSearch from '@/Components/Admin/AdminListSearch.vue';
import ComboFormFields from '@/Components/Admin/ComboFormFields.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ combos: Array, branches: Array, products: Array, filters: Object });

const page = usePage();
const tenant = page.props.tenant;
const editingId = ref(null);

const form = useForm({
    name: '',
    description: '',
    price: '',
    branch_id: '',
    is_active: true,
    image: null,
    items: [{ product_id: '', quantity: 1 }],
});

const editForm = useForm({
    name: '',
    description: '',
    price: '',
    branch_id: '',
    is_active: true,
    image: null,
    items: [{ product_id: '', quantity: 1 }],
});

const addItem = (target) => target.items.push({ product_id: '', quantity: 1 });

const submit = () =>
    form.post(route('tenant.admin.combos.store', { tenant: tenant.slug }), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            form.items = [{ product_id: '', quantity: 1 }];
        },
    });

const startEdit = (c) => {
    editingId.value = c.id;
    editForm.name = c.name;
    editForm.description = c.description ?? '';
    editForm.price = c.price;
    editForm.branch_id = c.branch_id ?? '';
    editForm.is_active = c.is_active;
    editForm.image = null;
    editForm.items = c.items.map((i) => ({ product_id: i.product_id, quantity: i.quantity }));
};

const submitEdit = (id) =>
    editForm
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route('tenant.admin.combos.update', { tenant: tenant.slug, combo: id }), {
            forceFormData: true,
            onSuccess: () => (editingId.value = null),
        });

const remove = (id) => router.delete(route('tenant.admin.combos.destroy', { tenant: tenant.slug, combo: id }));
</script>

<template>
    <Head title="Combos" />
    <h1 class="admin-page-title">Combos</h1>

    <form class="admin-card mt-6 space-y-4" @submit.prevent="submit">
        <h2 class="text-base font-semibold text-stone-900">Novo combo</h2>
        <ComboFormFields
            :form="form"
            :branches="branches"
            :products="products"
            @add-item="addItem(form)"
        />
        <div class="flex gap-2 border-t border-stone-100 pt-4">
            <button type="submit" class="admin-btn-primary" :disabled="form.processing">Criar combo</button>
        </div>
    </form>

    <AdminListSearch
        :href="route('tenant.admin.combos.index', { tenant: tenant.slug })"
        :filters="filters"
        placeholder="Buscar combo..."
    />

    <ul class="mt-6 space-y-3">
        <li v-for="c in combos" :key="c.id" class="admin-card">
            <form v-if="editingId === c.id" class="space-y-4" @submit.prevent="submitEdit(c.id)">
                <h2 class="text-base font-semibold text-stone-900">Editar combo</h2>
                <ComboFormFields
                    :form="editForm"
                    :branches="branches"
                    :products="products"
                    @add-item="addItem(editForm)"
                />
                <div class="flex gap-2 border-t border-stone-100 pt-4">
                    <button type="submit" class="admin-btn-primary" :disabled="editForm.processing">Salvar</button>
                    <button type="button" class="admin-btn-secondary" @click="editingId = null">Cancelar</button>
                </div>
            </form>
            <div v-else class="flex justify-between gap-4">
                <div>
                    <p class="font-semibold">{{ c.name }} — R$ {{ parseFloat(c.price).toFixed(2) }}</p>
                    <p class="text-xs text-stone-500">{{ c.branch_name }}</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <button type="button" class="text-sm text-orange-600" @click="startEdit(c)">Editar</button>
                    <button type="button" class="text-sm text-red-600" @click="remove(c.id)">Remover</button>
                </div>
            </div>
        </li>
    </ul>
</template>
