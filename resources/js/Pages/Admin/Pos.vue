<script setup>
import PosProductModal from '@/Components/Admin/PosProductModal.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    products: Array,
    combos: { type: Array, default: () => [] },
    branches: Array,
});

const page = usePage();
const tenant = page.props.tenant;

const cart = ref([]);
const branchId = ref(props.branches?.[0]?.id ?? null);
const modalProduct = ref(null);
const modalOpen = ref(false);

const MAX_QTY = 99;

const branchProducts = computed(() =>
    (props.products ?? []).filter(
        (p) => !branchId.value || (p.branch_ids ?? []).includes(branchId.value),
    ),
);

const branchCombos = computed(() =>
    (props.combos ?? []).filter((c) => !c.branch_id || c.branch_id === branchId.value),
);

const lineKey = (productId, variations = [], comboId = null) => {
    if (comboId) return `combo:${comboId}`;
    const ids = variations
        .map((v) => `${v.option_id}:${v.quantity ?? 1}`)
        .sort()
        .join('-');
    return `${productId}:${ids}`;
};

const addLine = (product, unitPrice, variations = [], quantity = 1, comboId = null) => {
    const key = lineKey(product?.id, variations, comboId);
    const label = comboId
        ? product.name
        : variations.length > 0
          ? `${product.name} (${variations.map((v) => (v.quantity > 1 ? `${v.option_name} ×${v.quantity}` : v.option_name)).join(', ')})`
          : product.name;
    const existing = cart.value.find((i) => i.line_key === key);
    if (existing) {
        if (existing.quantity < MAX_QTY) existing.quantity += quantity;
    } else {
        cart.value.push({
            line_key: key,
            product_id: comboId ? null : product.id,
            combo_id: comboId,
            name: label,
            unit_price: unitPrice,
            quantity,
            variations,
        });
    }
};

const addCombo = (combo) => addLine({ id: null, name: combo.name }, parseFloat(combo.price), [], 1, combo.id);

const hasVariations = (product) => product.has_customization || product.has_variations;

const openAddProduct = (product) => {
    if (hasVariations(product)) {
        modalProduct.value = product;
        modalOpen.value = true;
        return;
    }
    addLine(product, parseFloat(product.base_price), []);
};

const onModalConfirm = ({ product, unit_price, variations, quantity }) => {
    addLine(product, unit_price, variations, quantity ?? 1);
    modalOpen.value = false;
    modalProduct.value = null;
};

const increment = (item) => {
    if (item.quantity < MAX_QTY) item.quantity++;
};

const decrement = (item) => {
    if (item.quantity <= 1) remove(item);
    else item.quantity--;
};

const setQuantity = (item, raw) => {
    const n = parseInt(raw, 10);
    if (Number.isNaN(n) || n < 1) {
        item.quantity = 1;
        return;
    }
    item.quantity = Math.min(MAX_QTY, n);
};

const remove = (item) => {
    cart.value = cart.value.filter((i) => i.line_key !== item.line_key);
};

const clearCart = () => {
    cart.value = [];
};

const itemCount = computed(() => cart.value.reduce((s, i) => s + i.quantity, 0));

const total = computed(() => cart.value.reduce((s, i) => s + i.unit_price * i.quantity, 0));

const form = useForm({
    branch_id: branchId.value,
    guest_name: 'Balcão',
    guest_phone: '-',
    payment_method: 'on_delivery',
    mark_paid: false,
    items: [],
});

const submit = () => {
    if (!cart.value.length || !branchId.value) return;
    form.branch_id = branchId.value;
    form.items = cart.value.map((i) => ({
        product_id: i.product_id ?? undefined,
        combo_id: i.combo_id ?? undefined,
        name: i.name,
        quantity: i.quantity,
        unit_price: i.unit_price,
        variations: i.variations?.length ? i.variations : undefined,
    }));
    form.post(route('tenant.admin.pos.store', { tenant: tenant.slug }), {
        onSuccess: () => {
            cart.value = [];
            form.reset('guest_name', 'guest_phone');
            form.guest_name = 'Balcão';
            form.guest_phone = '-';
        },
    });
};

const formatPrice = (v) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const displayPrice = (product) => {
    if (product.has_variations || product.has_customization) {
        return `a partir de ${formatPrice(parseFloat(product.base_price))}`;
    }
    return formatPrice(parseFloat(product.base_price));
};
</script>

<template>
    <Head title="PDV" />

    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="admin-page-title">PDV — Balcão</h1>
            <p v-if="itemCount" class="text-sm text-stone-500">{{ itemCount }} itens no pedido</p>
        </div>
        <select v-model="branchId" class="admin-input w-auto">
            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <p v-if="!branchProducts.length" class="text-sm text-stone-500">
                Nenhum produto disponível nesta unidade.
            </p>
            <div v-if="branchCombos.length" class="mb-6">
                <h2 class="mb-2 text-sm font-semibold uppercase text-stone-500">Combos</h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <article v-for="c in branchCombos" :key="c.id" class="admin-card">
                        <p class="text-sm font-medium">{{ c.name }}</p>
                        <p class="text-orange-600">{{ formatPrice(parseFloat(c.price)) }}</p>
                        <button type="button" class="admin-btn-primary mt-2 w-full text-sm" @click="addCombo(c)">Adicionar</button>
                    </article>
                </div>
            </div>
            <div v-if="branchProducts.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                <article
                    v-for="p in branchProducts"
                    :key="p.id"
                    class="admin-card flex flex-col text-left"
                >
                    <p class="text-sm font-medium leading-tight text-stone-900">{{ p.name }}</p>
                    <p class="mt-1 text-orange-600">{{ displayPrice(p) }}</p>
                    <p v-if="hasVariations(p)" class="mt-1 text-xs text-stone-500">
                        Com opções e adicionais
                    </p>
                    <button
                        type="button"
                        class="admin-btn-primary mt-3 w-full text-sm"
                        @click="openAddProduct(p)"
                    >
                        {{ hasVariations(p) ? 'Personalizar' : 'Adicionar' }}
                    </button>
                </article>
            </div>
        </div>

        <aside class="admin-card lg:sticky lg:top-4 lg:self-start">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Pedido atual</h2>
                <button
                    v-if="cart.length"
                    type="button"
                    class="text-xs font-medium text-red-600 hover:underline"
                    @click="clearCart"
                >
                    Limpar
                </button>
            </div>

            <ul v-if="cart.length" class="mt-3 max-h-[50vh] space-y-3 overflow-y-auto">
                <li
                    v-for="item in cart"
                    :key="item.line_key"
                    class="rounded-xl border border-stone-200 bg-stone-50 p-3"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="min-w-0 flex-1 text-sm font-medium leading-tight text-stone-900">
                            {{ item.name }}
                        </p>
                        <button
                            type="button"
                            class="shrink-0 text-stone-400 hover:text-red-600"
                            title="Remover"
                            @click="remove(item)"
                        >
                            ✕
                        </button>
                    </div>
                    <p class="mt-0.5 text-xs text-stone-500">{{ formatPrice(item.unit_price) }} un.</p>

                    <div class="mt-2 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-lg border border-stone-200 bg-white text-lg font-medium text-stone-700 hover:bg-stone-100 active:scale-95"
                                aria-label="Diminuir"
                                @click="decrement(item)"
                            >
                                −
                            </button>
                            <input
                                :value="item.quantity"
                                type="number"
                                min="1"
                                :max="MAX_QTY"
                                class="admin-input h-9 w-14 px-1 text-center text-sm font-bold tabular-nums"
                                @change="setQuantity(item, $event.target.value)"
                            />
                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-lg text-lg font-medium text-white active:scale-95 disabled:opacity-40"
                                style="background-color: var(--admin-accent, #ea580c)"
                                aria-label="Aumentar"
                                :disabled="item.quantity >= MAX_QTY"
                                @click="increment(item)"
                            >
                                +
                            </button>
                        </div>
                        <span class="text-sm font-bold text-stone-900">
                            {{ formatPrice(item.unit_price * item.quantity) }}
                        </span>
                    </div>
                </li>
            </ul>
            <p v-else class="mt-3 text-sm text-stone-500">Use o botão Adicionar em cada produto.</p>

            <input v-model="form.guest_name" class="admin-input mt-4" placeholder="Nome do cliente" />
            <input v-model="form.guest_phone" class="admin-input mt-2" placeholder="Telefone" />
            <select v-model="form.payment_method" class="admin-input mt-2">
                <option value="on_delivery">Na entrega</option>
                <option value="pix">PIX</option>
                <option value="cash">Dinheiro (pago)</option>
                <option value="card">Cartão (pago)</option>
                <option value="debit">Débito (pago)</option>
            </select>
            <label v-if="form.payment_method !== 'on_delivery'" class="mt-2 flex items-center gap-2 text-sm">
                <input v-model="form.mark_paid" type="checkbox" /> Já recebido
            </label>

            <p class="mt-4 border-t pt-4 text-right text-lg font-bold">{{ formatPrice(total) }}</p>

            <button
                type="button"
                class="admin-btn-primary mt-4 w-full"
                :disabled="!cart.length || form.processing"
                @click="submit"
            >
                {{ form.processing ? 'Registrando...' : 'Registrar pedido' }}
            </button>
        </aside>
    </div>

    <PosProductModal
        :product="modalProduct"
        :open="modalOpen"
        @close="modalOpen = false"
        @confirm="onModalConfirm"
    />
</template>
