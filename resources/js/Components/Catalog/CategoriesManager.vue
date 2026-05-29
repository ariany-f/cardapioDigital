<script setup>
import AdminListSearch from '@/Components/Admin/AdminListSearch.vue';
import { useCatalogRoutes } from '@/composables/useCatalogRoutes';
import { usePanelUi } from '@/composables/usePanelUi';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    categories: Array,
    filters: { type: Object, default: () => ({}) },
    mode: { type: String, default: 'tenant' },
    platformTenant: Object,
});

const { categories: routes, products: productRoutes, backToTenant, fullAdminDashboard } = useCatalogRoutes(
    props.mode,
    props.platformTenant,
);

const { cls } = usePanelUi(() => props.mode);

const editingId = ref(null);

const form = useForm({ name: '', sort_order: 0, is_active: true });
const editForm = useForm({ name: '', sort_order: 0, is_active: true, is_paused: false });

const submit = () => form.post(routes.store(), { onSuccess: () => form.reset() });

const startEdit = (cat) => {
    editingId.value = cat.id;
    editForm.name = cat.name;
    editForm.sort_order = cat.sort_order;
    editForm.is_active = cat.is_active;
    editForm.is_paused = cat.is_paused;
};

const submitEdit = (id) =>
    editForm.put(routes.update(id), {
        onSuccess: () => (editingId.value = null),
    });

const remove = (id) => router.delete(routes.destroy(id));
</script>

<template>
    <Head :title="mode === 'platform' ? `Categorias — ${platformTenant?.name}` : 'Categorias'" />

    <div v-if="mode === 'platform'" class="mb-4 flex flex-wrap items-center gap-3 text-sm">
        <Link :href="backToTenant()" :class="[cls.link, 'hover:underline']"> ← {{ platformTenant.name }} </Link>
        <span class="text-stone-300">|</span>
        <Link :href="productRoutes.index()" :class="cls.linkMuted">Produtos</Link>
        <Link v-if="fullAdminDashboard()" :href="fullAdminDashboard()" :class="cls.link"> Painel completo → </Link>
    </div>

    <h1 :class="cls.pageTitle">Categorias</h1>

    <form :class="[cls.card, 'mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4']" @submit.prevent="submit">
        <input v-model="form.name" placeholder="Nome da categoria" :class="cls.input" required />
        <input v-model="form.sort_order" type="number" min="0" placeholder="Ordem" :class="cls.input" />
        <label class="flex items-center gap-2 text-sm text-stone-700 sm:col-span-2 lg:col-span-1">
            <input v-model="form.is_active" type="checkbox" class="rounded border-stone-300" />
            Ativa ao criar
        </label>
        <button type="submit" :class="[cls.btnPrimary, 'sm:col-span-2 lg:col-span-4']" :disabled="form.processing">
            Adicionar categoria
        </button>
    </form>

    <AdminListSearch :href="routes.index()" :filters="filters" placeholder="Buscar categoria..." />

    <div :class="[cls.tableWrap, 'mt-6']">
        <table :class="cls.table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Ordem</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="cat in categories" :key="cat.id">
                    <td>
                        <input v-if="editingId === cat.id" v-model="editForm.name" :class="cls.input" />
                        <span v-else class="font-medium text-stone-900">{{ cat.name }}</span>
                    </td>
                    <td>
                        <input
                            v-if="editingId === cat.id"
                            v-model="editForm.sort_order"
                            type="number"
                            min="0"
                            class="w-24"
                            :class="cls.input"
                        />
                        <span v-else>{{ cat.sort_order }}</span>
                    </td>
                    <td>
                        <template v-if="editingId === cat.id">
                            <label class="flex items-center gap-2 text-sm text-stone-700">
                                <input v-model="editForm.is_active" type="checkbox" class="rounded border-stone-300" />
                                Ativa
                            </label>
                            <label class="mt-1 flex items-center gap-2 text-sm text-stone-700">
                                <input v-model="editForm.is_paused" type="checkbox" class="rounded border-stone-300" />
                                Pausada
                            </label>
                        </template>
                        <template v-else>
                            <span v-if="cat.is_paused" class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                Pausada
                            </span>
                            <span
                                v-else-if="cat.is_active"
                                class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800"
                            >
                                Ativa
                            </span>
                            <span v-else class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-600"> Inativa </span>
                        </template>
                    </td>
                    <td class="whitespace-nowrap text-right">
                        <template v-if="editingId === cat.id">
                            <button type="button" :class="[cls.link, 'font-semibold hover:underline']" @click="submitEdit(cat.id)">
                                Salvar
                            </button>
                            <button type="button" class="ml-2 text-sm text-stone-500 hover:underline" @click="editingId = null">
                                Cancelar
                            </button>
                        </template>
                        <template v-else>
                            <button type="button" :class="[cls.link, 'font-semibold hover:underline']" @click="startEdit(cat)">
                                Editar
                            </button>
                            <button type="button" class="ml-2 text-sm text-red-600 hover:underline" @click="remove(cat.id)">Excluir</button>
                        </template>
                    </td>
                </tr>
            </tbody>
        </table>
        <p v-if="!categories?.length" class="px-4 py-8 text-center text-stone-500">Nenhuma categoria cadastrada.</p>
    </div>
</template>
