<script setup>
import VariationGroupsEditor from '@/Components/Admin/VariationGroupsEditor.vue';
import { useCatalogRoutes } from '@/composables/useCatalogRoutes';
import { usePanelUi } from '@/composables/usePanelUi';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    products: Object,
    categories: Array,
    branches: Array,
    editingProduct: Object,
    creating: Boolean,
    mode: { type: String, default: 'tenant' },
    platformTenant: Object,
});

const { products: routes, categories: catRoutes, backToTenant, fullAdminDashboard, publicMenu } = useCatalogRoutes(
    props.mode,
    props.platformTenant,
);

const { cls } = usePanelUi(() => props.mode);
const accent = computed(() => (props.mode === 'platform' ? 'platform' : 'admin'));

const allBranchIds = () => props.branches?.map((b) => b.id) ?? [];

const form = useForm({
    name: '',
    category_id: props.categories?.[0]?.id ?? '',
    description: '',
    base_price: '',
    tags: '',
    is_active: true,
    is_paused: false,
    is_featured: false,
    prep_time_minutes: '',
    track_stock: false,
    stock_quantity: '',
    branch_ids: allBranchIds(),
    variation_groups: [],
    image: null,
});

const editForm = useForm({
    id: null,
    name: '',
    category_id: '',
    description: '',
    base_price: '',
    tags: '',
    is_active: true,
    is_paused: false,
    is_featured: false,
    prep_time_minutes: '',
    track_stock: false,
    stock_quantity: '',
    branch_ids: [],
    variation_groups: [],
    image: null,
});

watch(
    () => props.editingProduct,
    (p) => {
        if (p) {
            editForm.id = p.id;
            editForm.name = p.name;
            editForm.category_id = p.category_id;
            editForm.description = p.description ?? '';
            editForm.base_price = p.base_price;
            editForm.tags = p.tags ?? '';
            editForm.is_active = p.is_active;
            editForm.is_paused = p.is_paused;
            editForm.is_featured = p.is_featured ?? false;
            editForm.prep_time_minutes = p.prep_time_minutes ?? '';
            editForm.track_stock = p.track_stock ?? false;
            editForm.stock_quantity = p.stock_quantity ?? '';
            editForm.branch_ids = p.branch_ids ?? [];
            editForm.variation_groups = p.variation_groups ?? [];
            editForm.image = null;
        }
    },
    { immediate: true },
);

const editingImageUrl = computed(() => props.editingProduct?.image_url ?? null);

const toggleBranch = (formObj, branchId) => {
    const idx = formObj.branch_ids.indexOf(branchId);
    if (idx >= 0) formObj.branch_ids.splice(idx, 1);
    else formObj.branch_ids.push(branchId);
};

const submit = () => form.post(routes.store(), { forceFormData: true });
const submitEdit = () =>
    editForm
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(routes.update(editForm.id), { forceFormData: true });
const remove = (id) => router.delete(routes.destroy(id));
</script>

<template>
    <Head :title="mode === 'platform' ? `Produtos — ${platformTenant?.name}` : 'Produtos'" />

    <div v-if="mode === 'platform'" class="mb-4 flex flex-wrap items-center gap-3 text-sm">
        <Link :href="backToTenant()" :class="[cls.link, 'hover:underline']"> ← {{ platformTenant.name }} </Link>
        <span class="text-stone-300">|</span>
        <Link :href="catRoutes.index()" :class="cls.linkMuted">Categorias</Link>
        <Link v-if="fullAdminDashboard()" :href="fullAdminDashboard()" :class="cls.link"> Painel completo → </Link>
        <a
            v-if="branches?.[0]"
            :href="publicMenu(branches[0].slug)"
            target="_blank"
            rel="noopener"
            :class="cls.linkMuted"
        >
            Ver cardápio ↗
        </a>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 :class="cls.pageTitle">Produtos</h1>
        <Link v-if="!creating && !editingProduct" :href="routes.create()" :class="cls.btnPrimary"> Novo produto </Link>
    </div>

    <form v-if="creating" :class="[cls.card, 'mt-6 grid gap-3 sm:grid-cols-2']" @submit.prevent="submit">
        <h2 class="font-semibold text-stone-900 sm:col-span-2">Novo produto</h2>
        <input v-model="form.name" placeholder="Nome" :class="cls.input" required />
        <select v-model="form.category_id" :class="cls.input" required>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <input v-model="form.base_price" type="number" step="0.01" min="0" placeholder="Preço" :class="cls.input" required />
        <textarea v-model="form.description" placeholder="Descrição" :class="[cls.input, 'sm:col-span-2']" rows="2" />
        <input v-model="form.tags" placeholder="Tags (vírgula)" :class="cls.input" />
        <div>
            <label class="mb-1 block text-sm font-medium text-stone-700">Tempo de preparo (min)</label>
            <input
                v-model="form.prep_time_minutes"
                type="number"
                min="1"
                placeholder="Padrão da filial"
                :class="cls.input"
            />
            <p class="mt-1 text-xs text-stone-500">Opcional. Vazio usa o tempo padrão da unidade.</p>
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-stone-700">Foto do produto</label>
            <input type="file" accept="image/*" :class="cls.input" @change="form.image = $event.target.files[0]" />
        </div>
        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input v-model="form.is_featured" type="checkbox" class="rounded border-stone-300" />
            Destaque
        </label>
        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input v-model="form.is_paused" type="checkbox" class="rounded border-stone-300" />
            Pausado
        </label>
        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input v-model="form.track_stock" type="checkbox" class="rounded border-stone-300" />
            Controlar estoque
        </label>
        <input v-if="form.track_stock" v-model="form.stock_quantity" type="number" min="0" placeholder="Qtd estoque" :class="cls.input" />
        <div class="sm:col-span-2">
            <p class="mb-2 text-sm font-medium text-stone-700">Disponível nas filiais</p>
            <div class="flex flex-wrap gap-2">
                <label
                    v-for="b in branches"
                    :key="b.id"
                    class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm"
                >
                    <input type="checkbox" class="rounded border-stone-300" :checked="form.branch_ids.includes(b.id)" @change="toggleBranch(form, b.id)" />
                    {{ b.name }}
                </label>
            </div>
        </div>
        <VariationGroupsEditor v-model="form.variation_groups" :input-class="cls.input" :accent-class="accent" />
        <div class="flex gap-2 sm:col-span-2">
            <Link :href="routes.index()" :class="cls.btnSecondary">Cancelar</Link>
            <button type="submit" :class="cls.btnPrimary" :disabled="form.processing">Salvar</button>
        </div>
    </form>

    <form v-if="editingProduct" :class="[cls.card, 'mt-6 grid gap-3 sm:grid-cols-2']" @submit.prevent="submitEdit">
        <h2 class="font-semibold text-stone-900 sm:col-span-2">Editar produto</h2>
        <input v-model="editForm.name" :class="cls.input" required />
        <select v-model="editForm.category_id" :class="cls.input" required>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <input v-model="editForm.base_price" type="number" step="0.01" min="0" :class="cls.input" required />
        <textarea v-model="editForm.description" :class="[cls.input, 'sm:col-span-2']" rows="2" />
        <input v-model="editForm.tags" placeholder="Tags" :class="cls.input" />
        <div>
            <label class="mb-1 block text-sm font-medium text-stone-700">Tempo de preparo (min)</label>
            <input
                v-model="editForm.prep_time_minutes"
                type="number"
                min="1"
                placeholder="Padrão da filial"
                :class="cls.input"
            />
            <p class="mt-1 text-xs text-stone-500">Opcional. Vazio usa o tempo padrão da unidade.</p>
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-medium text-stone-700">Foto do produto</label>
            <img
                v-if="editingImageUrl && !editForm.image"
                :src="editingImageUrl"
                alt=""
                class="mb-2 h-32 w-32 rounded-xl object-cover ring-1 ring-stone-200"
            />
            <input type="file" accept="image/*" :class="cls.input" @change="editForm.image = $event.target.files[0]" />
            <p class="mt-1 text-xs text-stone-500">Deixe em branco para manter a foto atual.</p>
        </div>
        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input v-model="editForm.is_active" type="checkbox" class="rounded border-stone-300" />
            Ativo
        </label>
        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input v-model="editForm.is_paused" type="checkbox" class="rounded border-stone-300" />
            Pausado
        </label>
        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input v-model="editForm.is_featured" type="checkbox" class="rounded border-stone-300" />
            Destaque
        </label>
        <label class="flex items-center gap-2 text-sm text-stone-700">
            <input v-model="editForm.track_stock" type="checkbox" class="rounded border-stone-300" />
            Estoque
        </label>
        <input v-if="editForm.track_stock" v-model="editForm.stock_quantity" type="number" min="0" :class="cls.input" />
        <div class="sm:col-span-2">
            <p class="mb-2 text-sm font-medium text-stone-700">Filiais</p>
            <div class="flex flex-wrap gap-2">
                <label
                    v-for="b in branches"
                    :key="b.id"
                    class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm"
                >
                    <input type="checkbox" class="rounded border-stone-300" :checked="editForm.branch_ids.includes(b.id)" @change="toggleBranch(editForm, b.id)" />
                    {{ b.name }}
                </label>
            </div>
        </div>
        <VariationGroupsEditor v-model="editForm.variation_groups" :input-class="cls.input" :accent-class="accent" />
        <div class="flex gap-2 sm:col-span-2">
            <Link :href="routes.index()" :class="cls.btnSecondary">Cancelar</Link>
            <button type="submit" :class="cls.btnPrimary" :disabled="editForm.processing">Atualizar</button>
        </div>
    </form>

    <div v-if="!creating && !editingProduct" :class="[cls.tableWrap, 'mt-6']">
        <table :class="cls.table">
            <thead>
                <tr>
                    <th class="w-14"></th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Filiais</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="product in products.data" :key="product.id">
                    <td>
                        <img
                            v-if="product.image_url"
                            :src="product.image_url"
                            :alt="product.name"
                            class="h-10 w-10 rounded-lg object-cover ring-1 ring-stone-200"
                        />
                        <span
                            v-else
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-stone-100 text-xs font-bold text-stone-400"
                        >
                            {{ product.name.charAt(0) }}
                        </span>
                    </td>
                    <td class="font-medium text-stone-900">{{ product.name }}</td>
                    <td>{{ product.category?.name ?? '—' }}</td>
                    <td>R$ {{ parseFloat(product.base_price).toFixed(2) }}</td>
                    <td class="text-xs text-stone-500">
                        <span v-if="product.branches_count !== undefined">{{ product.branches_count }} filial(is)</span>
                        <span v-else>—</span>
                    </td>
                    <td>
                        <span v-if="product.is_paused" class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                            Pausado
                        </span>
                        <span
                            v-else-if="product.is_active"
                            class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800"
                        >
                            Ativo
                        </span>
                        <span v-else class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-600"> Inativo </span>
                    </td>
                    <td class="whitespace-nowrap text-right">
                        <Link :href="routes.edit(product.id)" :class="[cls.link, 'font-semibold hover:underline']">Editar</Link>
                        <button type="button" class="ml-2 text-sm text-red-600 hover:underline" @click="remove(product.id)">Excluir</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <p v-if="!products.data?.length" class="px-4 py-8 text-center text-stone-500">Nenhum produto cadastrado.</p>
    </div>
</template>
