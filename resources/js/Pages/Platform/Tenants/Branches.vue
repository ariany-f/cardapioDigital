<script setup>
import AdminListSearch from '@/Components/Admin/AdminListSearch.vue';
import BranchFormFields from '@/Components/Platform/BranchFormFields.vue';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { branchFormFromModel, emptyBranchForm } from '@/composables/platformForms';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    tenant: Object,
    branches: Array,
    editingBranch: Object,
    creating: Boolean,
    filters: Object,
});

const form = useForm(emptyBranchForm());
const editForm = useForm(emptyBranchForm());

watch(
    () => props.editingBranch,
    (branch) => {
        if (branch) {
            editForm.defaults(branchFormFromModel(branch)).reset();
        }
    },
    { immediate: true },
);

const submitCreate = () =>
    form.post(route('platform.tenants.branches.store', props.tenant.id), { forceFormData: true });

const submitEdit = () =>
    editForm
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route('platform.tenants.branches.update', [props.tenant.id, editForm.id]), { forceFormData: true });

const remove = (id) => {
    if (confirm('Excluir esta filial?')) {
        router.delete(route('platform.tenants.branches.destroy', [props.tenant.id, id]));
    }
};

const copyLink = (url) => navigator.clipboard.writeText(url);

const formatRestaurantRating = (summary) => {
    if (!summary?.count || summary.restaurant == null) {
        return null;
    }

    return `★ ${summary.restaurant} (${summary.count})`;
};
</script>

<template>
    <Head :title="`Filiais — ${tenant.name}`" />

    <div class="mb-4">
        <Link :href="route('platform.tenants.show', tenant.id)" class="text-sm text-indigo-600 hover:underline">
            ← {{ tenant.name }}
        </Link>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3 text-sm">
        <Link
            :href="route('tenant.admin.dashboard', { tenant: tenant.slug })"
            class="rounded-lg bg-orange-600 px-3 py-1.5 font-medium text-white hover:bg-orange-700"
        >
            Painel completo
        </Link>
        <Link :href="route('platform.tenants.products.index', tenant.id)" class="text-slate-600 hover:text-indigo-600">Produtos</Link>
        <Link :href="route('platform.tenants.categories.index', tenant.id)" class="text-slate-600 hover:text-indigo-600">Categorias</Link>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Filiais</h1>
            <p class="text-sm text-slate-500">{{ tenant.name }} · /{{ tenant.slug }}</p>
        </div>
        <Link
            v-if="!creating && !editingBranch"
            :href="route('platform.tenants.branches.create', tenant.id)"
            class="platform-btn-primary"
        >
            Nova filial
        </Link>
    </div>

    <div
        v-if="tenant.rating_summary?.count"
        class="mt-4 grid gap-3 sm:grid-cols-3 lg:max-w-xl"
    >
        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-center shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Restaurante</p>
            <p class="mt-0.5 text-xl font-bold text-amber-600">{{ tenant.rating_summary.restaurant ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-center shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Pedido</p>
            <p class="mt-0.5 text-xl font-bold text-amber-600">{{ tenant.rating_summary.order ?? '—' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-center shadow-sm">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Entrega</p>
            <p class="mt-0.5 text-xl font-bold text-amber-600">{{ tenant.rating_summary.delivery ?? '—' }}</p>
        </div>
        <p class="sm:col-span-3 text-xs text-slate-500">
            Média geral do restaurante · {{ tenant.rating_summary.count }} avaliação(ões) públicas
        </p>
    </div>

    <form v-if="creating" class="platform-card mt-6 space-y-4" @submit.prevent="submitCreate">
        <h2 class="text-base font-semibold text-slate-900">Cadastrar filial</h2>
        <BranchFormFields :form="form" @cover-change="(f) => (form.cover_image = f)" />
        <div class="flex gap-3 border-t border-slate-100 pt-4">
            <Link :href="route('platform.tenants.branches.index', tenant.id)" class="platform-btn-secondary">Cancelar</Link>
            <button type="submit" class="platform-btn-primary" :disabled="form.processing">Salvar</button>
        </div>
    </form>

    <form v-if="editingBranch" class="platform-card mt-6 space-y-4" @submit.prevent="submitEdit">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Editar filial — {{ editingBranch.name }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ editingBranch.public_url }}</p>
        </div>
        <BranchFormFields
            :form="editForm"
            :cover-preview="editingBranch.cover_url"
            @cover-change="(f) => (editForm.cover_image = f)"
        />
        <div class="flex gap-3 border-t border-slate-100 pt-4">
            <Link :href="route('platform.tenants.branches.index', tenant.id)" class="platform-btn-secondary">Cancelar</Link>
            <button type="submit" class="platform-btn-primary" :disabled="editForm.processing">Salvar alterações</button>
        </div>
    </form>

    <AdminListSearch
        v-if="!creating && !editingBranch"
        :href="route('platform.tenants.branches.index', tenant.id)"
        :filters="filters"
        placeholder="Buscar filial..."
    />

    <ul v-if="!creating && !editingBranch" class="mt-6 space-y-3">
        <li v-for="branch in branches" :key="branch.id" class="platform-card">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-medium text-slate-900">{{ branch.name }}</p>
                        <span
                            v-if="formatRestaurantRating(branch.rating_summary)"
                            class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800"
                            :title="`${branch.rating_summary.count} avaliações nesta filial`"
                        >
                            {{ formatRestaurantRating(branch.rating_summary) }}
                        </span>
                    </div>
                    <p class="text-sm text-slate-500">/{{ branch.slug }} · {{ branch.city || '—' }}</p>
                    <p v-if="branch.street" class="mt-1 text-xs text-slate-400">
                        {{ branch.street }}, {{ branch.number }} — {{ branch.neighborhood }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full px-2 py-0.5" :class="branch.is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600'">
                            {{ branch.is_active ? 'Ativa' : 'Inativa' }}
                        </span>
                        <span v-if="branch.pickup_available" class="rounded bg-slate-100 px-2 py-0.5">Retirada</span>
                        <span v-if="branch.delivery_available" class="rounded bg-slate-100 px-2 py-0.5">
                            Entrega{{ branch.delivery_radius_km ? ` · ${branch.delivery_radius_km} km` : '' }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2 text-sm">
                    <Link :href="route('platform.tenants.branches.edit', [tenant.id, branch.id])" class="text-indigo-600 hover:underline">Editar</Link>
                    <button type="button" class="text-red-600 hover:underline" @click="remove(branch.id)">Excluir</button>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs">
                <span class="min-w-0 flex-1 truncate text-slate-600">{{ branch.public_url }}</span>
                <button type="button" class="shrink-0 font-medium text-indigo-600" @click="copyLink(branch.public_url)">Copiar link</button>
                <a :href="branch.public_url" target="_blank" rel="noopener" class="shrink-0 text-indigo-600">Abrir</a>
            </div>
        </li>
        <li v-if="!branches.length" class="platform-card text-center text-sm text-slate-500">
            {{ filters?.q ? 'Nenhuma filial encontrada.' : 'Nenhuma filial cadastrada.' }}
        </li>
    </ul>
</template>
