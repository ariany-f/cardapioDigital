<script setup>
import NavIcon from '@/Components/NavIcon.vue';
import { menuThemeVars, normalizeHex, textOnBackground } from '@/composables/useMenuTheme';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    product: { type: Object, default: null },
    open: { type: Boolean, default: false },
    /** Cor primária do restaurante (evita perder tema no Teleport para body). */
    themeColor: { type: String, default: '' },
});

const emit = defineEmits(['close', 'confirm']);

const page = usePage();

const primaryColor = computed(() =>
    normalizeHex(props.themeColor || page.props.tenant?.theme_primary_color),
);

const modalThemeStyle = computed(() => menuThemeVars(primaryColor.value));

const selections = ref({});
const optionQuantities = ref({});

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

const confirmButtonStyle = computed(() => {
    const bg = isValid.value ? primaryColor.value : '#57534e';
    return {
        backgroundColor: bg,
        color: textOnBackground(bg),
    };
});

const qtyButtonStyle = computed(() => ({
    backgroundColor: primaryColor.value,
    color: textOnBackground(primaryColor.value),
}));

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
                class="public-menu fixed inset-0 z-[60] flex items-end justify-center bg-black/55 sm:items-center sm:p-4"
                :style="modalThemeStyle"
                @click.self="emit('close')"
            >
                <div
                    class="flex max-h-[90vh] w-full max-w-md flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-2xl"
                    @click.stop
                >
                    <div class="shrink-0 border-b border-stone-200 bg-white px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <img
                                v-if="product.image_url"
                                :src="product.image_url"
                                :alt="product.name"
                                class="h-16 w-16 shrink-0 rounded-xl object-cover ring-1 ring-stone-200"
                            />
                            <div class="min-w-0">
                                <h3 class="text-lg font-semibold text-stone-900">{{ product.name }}</h3>
                                <p v-if="product.description" class="mt-0.5 line-clamp-2 text-xs text-stone-500">
                                    {{ product.description }}
                                </p>
                                <p class="mt-1 text-base font-bold" :style="{ color: primaryColor }">
                                    {{ formatPrice(unitPrice) }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 rounded-full p-2 text-stone-500 hover:bg-stone-100"
                                aria-label="Fechar"
                                @click="emit('close')"
                            >
                                <NavIcon name="x" size="sm" />
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
                                                ? 'border-transparent ring-2 ring-[var(--menu-primary)]'
                                                : 'border-stone-200 bg-white hover:border-stone-300'
                                        "
                                        :style="
                                            isSelected(group.id, opt.id)
                                                ? { backgroundColor: 'var(--menu-primary-soft)' }
                                                : {}
                                        "
                                    >
                                        <label class="flex cursor-pointer items-center justify-between gap-2 text-stone-900">
                                            <span class="flex items-center gap-2 text-sm">
                                                <input
                                                    v-if="group.max_select === 1"
                                                    type="radio"
                                                    :name="`vg-${group.id}`"
                                                    :checked="selections[group.id] === opt.id"
                                                    :style="{ accentColor: primaryColor }"
                                                    @change="selections[group.id] = opt.id"
                                                />
                                                <input
                                                    v-else
                                                    type="checkbox"
                                                    :checked="isSelected(group.id, opt.id)"
                                                    :style="{ accentColor: primaryColor }"
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
                                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-base font-medium text-stone-800 ring-1 ring-stone-200"
                                                @click="setOptionQty(group, opt, getOptionQty(group, opt) - 1)"
                                            >
                                                −
                                            </button>
                                            <span class="min-w-[1.5rem] text-center text-sm font-bold tabular-nums text-stone-900">
                                                {{ getOptionQty(group, opt) }}
                                            </span>
                                            <button
                                                type="button"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-base font-medium shadow-sm"
                                                :style="qtyButtonStyle"
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
                        <button
                            type="button"
                            class="w-full rounded-xl px-5 py-3.5 text-sm font-bold shadow-md transition active:scale-[0.98] disabled:cursor-not-allowed"
                            :class="isValid ? 'hover:brightness-[0.92]' : ''"
                            :disabled="!isValid"
                            :style="confirmButtonStyle"
                            @click="confirm"
                        >
                            Adicionar à sacola · {{ formatPrice(unitPrice) }}
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
