<script setup>
import AdminListSearch from '@/Components/Admin/AdminListSearch.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ branch: Object, zones: Array, filters: Object });

const page = usePage();
const tenant = page.props.tenant;

const editingId = ref(null);

const emptyForm = () => ({
    name: '',
    type: 'flat',
    delivery_fee: '',
    fee_per_km: '',
    min_order_override: '',
    neighborhoods_text: '',
    is_active: true,
});

const form = useForm(emptyForm());
const editForm = useForm(emptyForm());

const zoneTypeLabel = (type) =>
    ({ flat: 'Taxa fixa', neighborhood: 'Por bairro', per_km: 'Por quilômetro' }[type] ?? type);

const formatZoneFee = (z) => {
    if (z.type === 'per_km') {
        const base = parseFloat(z.delivery_fee) || 0;
        const perKm = parseFloat(z.fee_per_km ?? z.rules?.fee_per_km) || 0;
        const parts = [];
        if (base > 0) parts.push(`R$ ${base.toFixed(2)} base`);
        if (perKm > 0) parts.push(`R$ ${perKm.toFixed(2)}/km`);
        return parts.length ? parts.join(' + ') : '—';
    }
    return `R$ ${parseFloat(z.delivery_fee).toFixed(2)}`;
};

const submit = () =>
    form.post(route('tenant.admin.branches.zones.store', { tenant: tenant.slug, branch: props.branch.id }), {
        onSuccess: () => form.reset(),
    });

const neighborhoodsText = (zone) => (zone.rules?.neighborhoods ?? []).join('\n');

const startEdit = (z) => {
    editingId.value = z.id;
    editForm.name = z.name;
    editForm.type = z.type;
    editForm.delivery_fee = z.delivery_fee;
    editForm.fee_per_km = z.fee_per_km ?? z.rules?.fee_per_km ?? '';
    editForm.min_order_override = z.min_order_override ?? '';
    editForm.neighborhoods_text = neighborhoodsText(z);
    editForm.is_active = z.is_active;
};

const submitEdit = (id) =>
    editForm.put(route('tenant.admin.branches.zones.update', { tenant: tenant.slug, branch: props.branch.id, zone: id }), {
        onSuccess: () => (editingId.value = null),
    });

const remove = (id) =>
    router.delete(route('tenant.admin.branches.zones.destroy', { tenant: tenant.slug, branch: props.branch.id, zone: id }));
</script>

<template>
    <Head :title="`Zonas — ${branch.name}`" />

    <Link :href="route('tenant.admin.branches.index', { tenant: tenant.slug })" class="text-sm text-stone-500">← Filiais</Link>
    <h1 class="admin-page-title mt-2">Zonas de entrega — {{ branch.name }}</h1>
    <p class="mt-1 text-sm text-stone-500">
        Taxa fixa, por bairro ou por km (exige localização do cliente e coordenadas da filial).
    </p>

    <form class="admin-card mt-6 grid gap-3 sm:grid-cols-2" @submit.prevent="submit">
        <input v-model="form.name" class="admin-input" placeholder="Nome da zona" required />
        <select v-model="form.type" class="admin-input">
            <option value="flat">Taxa fixa</option>
            <option value="neighborhood">Por bairro</option>
            <option value="per_km">Por quilômetro</option>
        </select>
        <input
            v-if="form.type !== 'per_km'"
            v-model="form.delivery_fee"
            type="number"
            step="0.01"
            min="0"
            class="admin-input"
            placeholder="Taxa (R$)"
            required
        />
        <template v-else>
            <input
                v-model="form.delivery_fee"
                type="number"
                step="0.01"
                min="0"
                class="admin-input"
                placeholder="Taxa base (R$, opcional)"
            />
            <input
                v-model="form.fee_per_km"
                type="number"
                step="0.01"
                min="0"
                class="admin-input"
                placeholder="Valor por km (R$)"
                required
            />
        </template>
        <input v-model="form.min_order_override" type="number" step="0.01" min="0" class="admin-input" placeholder="Pedido mínimo (opcional)" />
        <textarea
            v-if="form.type === 'neighborhood'"
            v-model="form.neighborhoods_text"
            class="admin-input sm:col-span-2"
            rows="3"
            placeholder="Bairros (um por linha)"
        />
        <button type="submit" class="admin-btn-primary sm:col-span-2">Adicionar zona</button>
    </form>

    <AdminListSearch
        :href="route('tenant.admin.branches.zones', { tenant: tenant.slug, branch: branch.id })"
        :filters="filters"
        placeholder="Buscar zona..."
    />

    <ul class="mt-6 space-y-3">
        <li v-for="z in zones" :key="z.id" class="admin-card">
            <form v-if="editingId === z.id" class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitEdit(z.id)">
                <input v-model="editForm.name" class="admin-input" required />
                <select v-model="editForm.type" class="admin-input">
                    <option value="flat">Taxa fixa</option>
                    <option value="neighborhood">Por bairro</option>
                    <option value="per_km">Por quilômetro</option>
                </select>
                <input
                    v-if="editForm.type !== 'per_km'"
                    v-model="editForm.delivery_fee"
                    type="number"
                    step="0.01"
                    min="0"
                    class="admin-input"
                    required
                />
                <template v-else>
                    <input
                        v-model="editForm.delivery_fee"
                        type="number"
                        step="0.01"
                        min="0"
                        class="admin-input"
                        placeholder="Taxa base (opcional)"
                    />
                    <input
                        v-model="editForm.fee_per_km"
                        type="number"
                        step="0.01"
                        min="0"
                        class="admin-input"
                        placeholder="Valor por km (R$)"
                        required
                    />
                </template>
                <input v-model="editForm.min_order_override" type="number" step="0.01" min="0" class="admin-input" />
                <textarea
                    v-if="editForm.type === 'neighborhood'"
                    v-model="editForm.neighborhoods_text"
                    class="admin-input sm:col-span-2"
                    rows="3"
                />
                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input v-model="editForm.is_active" type="checkbox" />
                    Ativa
                </label>
                <div class="flex gap-2 sm:col-span-2">
                    <button type="submit" class="admin-btn-primary">Salvar</button>
                    <button type="button" class="admin-btn-secondary" @click="editingId = null">Cancelar</button>
                </div>
            </form>
            <template v-else>
                <div class="flex justify-between gap-4">
                    <div>
                        <p class="font-semibold">{{ z.name }} — {{ formatZoneFee(z) }}</p>
                        <p class="text-sm text-stone-500">
                            {{ zoneTypeLabel(z.type) }}
                            <span v-if="!z.is_active" class="text-amber-600"> (inativa)</span>
                        </p>
                        <p v-if="z.type === 'neighborhood' && z.rules?.neighborhoods?.length" class="mt-1 text-xs text-stone-400">
                            {{ z.rules.neighborhoods.join(', ') }}
                        </p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button type="button" class="text-sm text-orange-600" @click="startEdit(z)">Editar</button>
                        <button type="button" class="text-sm text-red-600" @click="remove(z.id)">Remover</button>
                    </div>
                </div>
            </template>
        </li>
    </ul>
</template>
