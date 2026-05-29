<script setup>
import BranchFormFields from '@/Components/Platform/BranchFormFields.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { branchFormFromModel, emptyBranchForm } from '@/composables/platformForms';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    branches: Array,
    editingBranch: Object,
    creating: Boolean,
});

const page = usePage();
const tenant = page.props.tenant;
const coverPreview = ref(null);

const form = useForm({ ...emptyBranchForm() });
const editForm = useForm({ ...emptyBranchForm() });

watch(
    () => props.editingBranch,
    (b) => {
        if (b) {
            Object.assign(editForm, branchFormFromModel(b));
            coverPreview.value = b.cover_url ?? null;
        }
    },
    { immediate: true },
);

const submit = () =>
    form.post(route('tenant.admin.branches.store', { tenant: tenant.slug }), { forceFormData: true });

const submitEdit = () =>
    editForm
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route('tenant.admin.branches.update', { tenant: tenant.slug, branch: editForm.id }), {
            forceFormData: true,
        });

const onCover = (file, target) => {
    target.cover_image = file;
    coverPreview.value = file ? URL.createObjectURL(file) : props.editingBranch?.cover_url ?? null;
};

const remove = (id) => router.delete(route('tenant.admin.branches.destroy', { tenant: tenant.slug, branch: id }));
</script>

<template>
    <Head title="Filiais" />

    <div class="flex items-center justify-between">
        <h1 class="admin-page-title">Filiais</h1>
        <Link :href="route('tenant.admin.branches.create', { tenant: tenant.slug })" class="admin-btn-primary">
            Nova filial
        </Link>
    </div>

    <form v-if="creating" class="admin-card mt-6 space-y-4" @submit.prevent="submit">
        <h2 class="text-base font-semibold text-stone-900">Nova filial</h2>
        <BranchFormFields
            :form="form"
            input-class="admin-input"
            label-class="mb-1 block text-sm font-medium text-stone-700"
            @cover-change="onCover($event, form)"
        />
        <div class="flex gap-2 border-t border-stone-100 pt-4">
            <Link :href="route('tenant.admin.branches.index', { tenant: tenant.slug })" class="admin-btn-secondary">Cancelar</Link>
            <button type="submit" class="admin-btn-primary" :disabled="form.processing">Salvar</button>
        </div>
    </form>

    <form v-if="editingBranch" class="admin-card mt-6 space-y-4" @submit.prevent="submitEdit">
        <div>
            <h2 class="text-base font-semibold text-stone-900">Editar {{ editingBranch.name }}</h2>
            <p class="mt-1 text-xs text-stone-500">{{ editingBranch.public_url }}</p>
        </div>
        <BranchFormFields
            :form="editForm"
            input-class="admin-input"
            label-class="mb-1 block text-sm font-medium text-stone-700"
            :cover-preview="coverPreview"
            @cover-change="onCover($event, editForm)"
        />
        <div class="flex gap-2 border-t border-stone-100 pt-4">
            <Link :href="route('tenant.admin.branches.index', { tenant: tenant.slug })" class="admin-btn-secondary">Cancelar</Link>
            <button type="submit" class="admin-btn-primary" :disabled="editForm.processing">Atualizar</button>
        </div>
    </form>

    <ul class="mt-6 space-y-3">
        <li v-for="branch in branches" :key="branch.id" class="admin-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-semibold text-stone-900">{{ branch.name }}</p>
                    <p class="text-sm text-stone-500">/{{ branch.slug }} · {{ branch.city || '—' }}</p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        <span :class="branch.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-100 text-stone-600'" class="rounded-full px-2 py-0.5">
                            {{ branch.is_active ? 'Ativa' : 'Inativa' }}
                        </span>
                        <span v-if="branch.delivery_available" class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-600">Entrega</span>
                        <span v-if="branch.pickup_available" class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-600">Retirada</span>
                        <span
                            :class="branch.auto_accept_orders ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900'"
                            class="rounded-full px-2 py-0.5"
                        >
                            {{ branch.auto_accept_orders ? 'Aprovação automática' : 'Aprovação manual' }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('tenant.admin.branches.zones', { tenant: tenant.slug, branch: branch.id })"
                        class="text-sm font-medium text-stone-600 hover:text-orange-600"
                    >
                        Zonas
                    </Link>
                    <Link
                        :href="route('tenant.admin.branches.edit', { tenant: tenant.slug, branch: branch.id })"
                        class="text-sm font-medium text-orange-600 hover:text-orange-700"
                    >
                        Editar
                    </Link>
                    <button type="button" class="text-sm text-red-600" @click="remove(branch.id)">Excluir</button>
                </div>
            </div>
            <a :href="branch.public_url" target="_blank" rel="noopener" class="mt-2 block truncate text-xs text-stone-500 hover:text-orange-600">
                {{ branch.public_url }} ↗
            </a>
        </li>
    </ul>
</template>
