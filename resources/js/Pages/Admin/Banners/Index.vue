<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ banners: Array, branches: Array });

const page = usePage();
const tenant = page.props.tenant;
const editingId = ref(null);

const form = useForm({
    branch_id: '',
    title: '',
    link_url: '',
    sort_order: 0,
    is_active: true,
    image: null,
});

const editForm = useForm({
    title: '',
    link_url: '',
    sort_order: 0,
    is_active: true,
    image: null,
});

const submit = () =>
    form.post(route('tenant.admin.banners.store', { tenant: tenant.slug }), {
        forceFormData: true,
        onSuccess: () => form.reset(),
    });

const startEdit = (b) => {
    editingId.value = b.id;
    editForm.title = b.title ?? '';
    editForm.link_url = b.link_url ?? '';
    editForm.sort_order = b.sort_order ?? 0;
    editForm.is_active = b.is_active;
    editForm.image = null;
};

const submitEdit = (id) =>
    editForm
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route('tenant.admin.banners.update', { tenant: tenant.slug, banner: id }), {
            forceFormData: true,
            onSuccess: () => (editingId.value = null),
        });

const remove = (id) => router.delete(route('tenant.admin.banners.destroy', { tenant: tenant.slug, banner: id }));
</script>

<template>
    <Head title="Banners" />
    <h1 class="admin-page-title">Banners do cardápio</h1>

    <form class="admin-card mt-6 grid gap-3 sm:grid-cols-2" @submit.prevent="submit">
        <select v-model="form.branch_id" class="admin-input" required>
            <option value="" disabled>Filial</option>
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
        <input v-model="form.title" class="admin-input" placeholder="Título" />
        <input v-model="form.link_url" class="admin-input" placeholder="Link (opcional)" />
        <input v-model="form.sort_order" type="number" min="0" class="admin-input" placeholder="Ordem" />
        <input type="file" accept="image/*" class="admin-input sm:col-span-2" required @change="form.image = $event.target.files[0]" />
        <button type="submit" class="admin-btn-primary sm:col-span-2">Publicar banner</button>
    </form>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div v-for="b in banners" :key="b.id" class="admin-card">
            <form v-if="editingId === b.id" class="space-y-2" @submit.prevent="submitEdit(b.id)">
                <input v-model="editForm.title" class="admin-input" placeholder="Título" />
                <input v-model="editForm.link_url" class="admin-input" placeholder="Link" />
                <input v-model="editForm.sort_order" type="number" min="0" class="admin-input" />
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="editForm.is_active" type="checkbox" />
                    Ativo
                </label>
                <input type="file" accept="image/*" class="admin-input" @change="editForm.image = $event.target.files[0]" />
                <div class="flex gap-2">
                    <button type="submit" class="admin-btn-primary">Salvar</button>
                    <button type="button" class="admin-btn-secondary" @click="editingId = null">Cancelar</button>
                </div>
            </form>
            <template v-else>
                <img v-if="b.image_url" :src="b.image_url" class="mb-2 h-24 w-full rounded-lg object-cover" />
                <p class="font-medium">{{ b.title || 'Sem título' }}</p>
                <p class="text-xs text-stone-500">{{ b.branch_name }}</p>
                <div class="mt-2 flex gap-3">
                    <button type="button" class="text-sm text-orange-600" @click="startEdit(b)">Editar</button>
                    <button type="button" class="text-sm text-red-600" @click="remove(b.id)">Remover</button>
                </div>
            </template>
        </div>
    </div>
</template>
