<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    product: { type: Object, default: null },
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'confirm']);

const selections = ref({});
const optionQuantities = ref({});
const productQuantity = ref(1);

const sectionMeta = {
    choice: { title: 'Escolha', hint: 'Obrigatório' },
    addon: { title: 'Adicionais', hint: 'Opcional' },
    disposable: { title: 'Descartáveis', hint: 'Opcional' },
};

const groupsByType = computed(() => {
    if (!props.product) return { choice: [], addon: [], disposable: [] };
    return {
        choice: props.product.choice_groups ?? [],
        addon: props.product.addon_groups ?? [],
        disposable: props.product.disposable_groups ?? [],
    };
});

const allGroups = computed(() => [
    ...groupsByType.value.choice,
    ...groupsByType.value.addon,
    ...groupsByType.value.disposable,
]);

const qtyKey = (groupId, optionId) => `${groupId}-${optionId}`;

const groupUsesQuantity = (group) =>
    group.allow_quantity || group.type === 'disposable';

const maxQtyForOption = (group, opt) =>
    parseInt(opt.max_quantity ?? group.max_select ?? 1, 10) || 1;

const getOptionQty = (group, opt) => {
    const key = qtyKey(group.id, opt.id);
    const max = maxQtyForOption(group, opt);
    const raw = parseInt(optionQuantities.value[key], 10) || 1;
    return Math.max(1, Math.min(max, raw));
};

const setOptionQty = (group, opt, qty) => {
    const key = qtyKey(group.id, opt.id);
    const max = maxQtyForOption(group, opt);
    optionQuantities.value[key] = Math.max(1, Math.min(max, parseInt(qty, 10) || 1));
};

watch(
    () => props.product,
    (p) => {
        selections.value = {};
        optionQuantities.value = {};
        productQuantity.value = 1;
        if (!p) return;
        for (const g of allGroups.value) {
            if (g.type === 'choice' && g.min_select >= 1 && g.options?.length) {
                selections.value[g.id] = g.max_select === 1 ? g.options[0].id : [];
            } else if (g.type === 'disposable' && g.min_select >= 1 && g.options?.length) {
                selections.value[g.id] = g.max_select === 1 ? g.options[0].id : [];
            } else {
                selections.value[g.id] = g.max_select === 1 ? null : [];
            }
            for (const opt of g.options ?? []) {
                optionQuantities.value[qtyKey(g.id, opt.id)] = 1;
            }
        }
    },
    { immediate: true },
);

const formatPrice = (value) =>
    parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const unitPrice = computed(() => {
    if (!props.product) return 0;
    let extra = 0;
    for (const g of allGroups.value) {
        const sel = selections.value[g.id];
        const ids = Array.isArray(sel) ? sel : sel ? [sel] : [];
        for (const optId of ids) {
            const opt = g.options.find((o) => o.id === optId);
            if (opt) {
                const qty = groupUsesQuantity(g) ? getOptionQty(g, opt) : 1;
                extra += parseFloat(opt.additional_price) * qty;
            }
        }
    }
    return parseFloat(props.product.base_price) + extra;
});

const isValid = computed(() => {
    for (const g of allGroups.value) {
        const sel = selections.value[g.id];
        const count = Array.isArray(sel) ? sel.length : sel ? 1 : 0;
        if (count < g.min_select) return false;
        if (g.max_select && count > g.max_select) return false;
    }
    return true;
});

const groupHint = (group) => {
    if (group.min_select > 0 && group.max_select === 1) return 'Escolha 1 opção';
    if (group.min_select > 0) return `Escolha pelo menos ${group.min_select}`;
    if (group.max_select > 1) return `Escolha até ${group.max_select}`;
    if (groupUsesQuantity(group)) return 'Defina a quantidade de cada item';
    if (group.type === 'addon') return 'Toque para adicionar';
    return 'Opcional';
};

const isSelected = (groupId, optionId) => {
    const sel = selections.value[groupId];
    return Array.isArray(sel) ? sel.includes(optionId) : sel === optionId;
};

const toggleMulti = (groupId, optionId, maxSelect) => {
    const current = Array.isArray(selections.value[groupId]) ? [...selections.value[groupId]] : [];
    const idx = current.indexOf(optionId);
    if (idx >= 0) current.splice(idx, 1);
    else if (!maxSelect || current.length < maxSelect) current.push(optionId);
    selections.value[groupId] = current;
};

const buildVariations = () => {
    const list = [];
    for (const g of allGroups.value) {
        const sel = selections.value[g.id];
        const ids = Array.isArray(sel) ? sel : sel ? [sel] : [];
        for (const optId of ids) {
            const opt = g.options.find((o) => o.id === optId);
            if (opt) {
                const quantity = groupUsesQuantity(g) ? getOptionQty(g, opt) : 1;
                list.push({
                    type: g.type,
                    group_id: g.id,
                    group_name: g.name,
                    option_id: opt.id,
                    option_name: opt.name,
                    quantity,
                    additional_price: parseFloat(opt.additional_price),
                });
            }
        }
    }
    return list;
};

const confirm = () => {
    if (!isValid.value) return;
    emit('confirm', {
        product: props.product,
        unit_price: unitPrice.value,
        variations: buildVariations(),
        quantity: Math.min(99, Math.max(1, parseInt(productQuantity.value, 10) || 1)),
    });
    emit('close');
};

const sections = computed(() =>
    ['choice', 'addon', 'disposable']
        .filter((type) => groupsByType.value[type]?.length)
        .map((type) => ({ type, groups: groupsByType.value[type] })),
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open && product"
                class="fixed inset-0 z-[60] flex items-end justify-center bg-black/55 sm:items-center sm:p-4"
                @click.self="emit('close')"
            >
                <div
                    class="flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl"
                    @click.stop
                >
                    <div class="shrink-0 border-b border-stone-200 px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-lg font-semibold text-stone-900">{{ product.name }}</h3>
                                <p v-if="product.description" class="mt-0.5 line-clamp-2 text-xs text-stone-500">
                                    {{ product.description }}
                                </p>
                                <p class="mt-1 text-base font-bold text-orange-600">
                                    {{ formatPrice(unitPrice) }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 rounded-full p-2 text-stone-500 hover:bg-stone-100"
                                aria-label="Fechar"
                                @click="emit('close')"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5">
                        <section v-for="section in sections" :key="section.type">
                            <div class="mb-3 flex items-baseline justify-between gap-2">
                                <h4 class="text-sm font-semibold uppercase tracking-wide text-stone-800">
                                    {{ sectionMeta[section.type].title }}
                                </h4>
                                <span class="text-xs text-stone-400">{{ sectionMeta[section.type].hint }}</span>
                            </div>

                            <div v-for="group in section.groups" :key="group.id" class="mb-4 last:mb-0">
                                <p class="mb-1 text-sm font-medium text-stone-800">
                                    {{ group.name }}
                                    <span v-if="group.min_select" class="text-orange-600">*</span>
                                </p>
                                <p class="mb-2 text-xs text-stone-500">{{ groupHint(group) }}</p>
                                <div class="space-y-2">
                                    <div
                                        v-for="opt in group.options"
                                        :key="opt.id"
                                        class="rounded-xl border px-3 py-2.5 transition"
                                        :class="
                                            isSelected(group.id, opt.id)
                                                ? 'border-orange-300 bg-orange-50 ring-2 ring-orange-200'
                                                : 'border-stone-200 bg-white hover:border-stone-300'
                                        "
                                    >
                                        <label class="flex cursor-pointer items-center justify-between gap-2 text-stone-900">
                                            <span class="flex items-center gap-2 text-sm">
                                                <input
                                                    v-if="group.max_select === 1"
                                                    type="radio"
                                                    :name="`pos-vg-${group.id}`"
                                                    :checked="selections[group.id] === opt.id"
                                                    class="accent-orange-600"
                                                    @change="selections[group.id] = opt.id"
                                                />
                                                <input
                                                    v-else
                                                    type="checkbox"
                                                    :checked="isSelected(group.id, opt.id)"
                                                    class="accent-orange-600"
                                                    @change="toggleMulti(group.id, opt.id, group.max_select)"
                                                />
                                                {{ opt.name }}
                                            </span>
                                            <span
                                                class="text-xs font-semibold"
                                                :class="parseFloat(opt.additional_price) > 0 ? 'text-stone-700' : 'text-emerald-700'"
                                            >
                                                {{
                                                    parseFloat(opt.additional_price) > 0
                                                        ? '+ ' + formatPrice(opt.additional_price)
                                                        : 'Grátis'
                                                }}
                                            </span>
                                        </label>
                                        <div
                                            v-if="isSelected(group.id, opt.id) && groupUsesQuantity(group)"
                                            class="mt-2 flex items-center justify-end gap-2"
                                            @click.stop
                                        >
                                            <button
                                                type="button"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-stone-200 bg-white text-base font-medium"
                                                @click="setOptionQty(group, opt, getOptionQty(group, opt) - 1)"
                                            >
                                                −
                                            </button>
                                            <span class="min-w-[1.5rem] text-center text-sm font-bold tabular-nums">
                                                {{ getOptionQty(group, opt) }}
                                            </span>
                                            <button
                                                type="button"
                                                class="admin-btn-primary flex h-8 w-8 items-center justify-center rounded-lg !px-0 text-base font-medium"
                                                @click="setOptionQty(group, opt, getOptionQty(group, opt) + 1)"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="shrink-0 border-t border-stone-200 bg-stone-50 px-4 py-4">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-sm font-medium text-stone-700">Quantidade</span>
                            <div class="flex items-center gap-2">
                                <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg border" @click="productQuantity = Math.max(1, productQuantity - 1)">−</button>
                                <input v-model.number="productQuantity" type="number" min="1" max="99" class="admin-input h-9 w-14 text-center" />
                                <button type="button" class="admin-btn-primary flex h-9 w-9 items-center justify-center !px-0" @click="productQuantity = Math.min(99, productQuantity + 1)">+</button>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="admin-btn-primary w-full"
                            :disabled="!isValid"
                            @click="confirm"
                        >
                            Adicionar {{ productQuantity > 1 ? productQuantity + '× ' : '' }}· {{ formatPrice(unitPrice * productQuantity) }}
                        </button>
                        <p v-if="!isValid" class="mt-2 text-center text-xs font-medium text-amber-800">
                            Selecione as opções obrigatórias para continuar
                        </p>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
