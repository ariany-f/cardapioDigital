<script setup>
import NavIcon from '@/Components/NavIcon.vue';
import { clampDisposableQty, formatVariationLabel, normalizeOrderDisposables } from '@/composables/useDisposables';
import {
    formatCepInput,
    formatCepWithSelection,
    forwardGeocode,
    getBrowserPosition,
    isValidCep,
    lookupViaCep,
    normalizeCep,
    resolveDeliveryAddress,
    reverseGeocode,
} from '@/composables/useDeliveryAddressLookup';
import { estimateOrderMinutes, formatEstimateMinutes } from '@/composables/useOrderDeliveryEstimate';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    cart: { type: Array, required: true },
    branch: { type: Object, required: true },
    canOrder: { type: Boolean, default: false },
    canCheckout: { type: Boolean, default: true },
    loginUrl: { type: String, default: '' },
    guestName: { type: String, default: '' },
    guestPhone: { type: String, default: '' },
    guestEmail: { type: String, default: '' },
    showGuestEmail: { type: Boolean, default: false },
    orderType: { type: String, default: 'pickup' },
    couponCode: { type: String, default: '' },
    deliveryAddress: { type: Object, default: () => ({}) },
    orderDisposables: { type: Object, default: () => ({}) },
    tipAmount: { type: [String, Number], default: '' },
    scheduledFor: { type: String, default: '' },
    paymentMethod: { type: String, default: 'on_delivery' },
    paymentChannel: { type: String, default: 'pix' },
    paymentSettings: { type: Object, default: () => ({}) },
    table: { type: Object, default: null },
    processing: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
    productsById: { type: Map, default: () => new Map() },
});

const isDineIn = computed(() => props.orderType === 'dine_in' || !!props.table);

const emit = defineEmits([
    'update:guestName',
    'update:guestPhone',
    'update:guestEmail',
    'update:orderType',
    'update:couponCode',
    'update:deliveryAddress',
    'update:orderDisposables',
    'update:tipAmount',
    'update:scheduledFor',
    'update:paymentMethod',
    'update:paymentChannel',
    'increment',
    'decrement',
    'checkout',
    'close',
]);

const step = ref('items');
const deliveryLat = ref(null);
const deliveryLng = ref(null);
const cepLoading = ref(false);
const cepMessage = ref('');
const cepError = ref('');
const geoError = ref('');
const locating = ref(false);
const addressLocked = ref(false);
let cepLookupTimer = null;
const cepInputRef = ref(null);

const subtotal = computed(() =>
    props.cart.reduce((sum, item) => sum + item.unit_price * item.quantity, 0),
);

const tipValue = computed(() => Math.max(0, parseFloat(props.tipAmount) || 0));

const displayTotal = computed(() => subtotal.value + tipValue.value);

const itemCount = computed(() => props.cart.reduce((sum, item) => sum + item.quantity, 0));

const minOrder = computed(() => parseFloat(props.branch.minimum_order_amount || 0));
const minOrderProgress = computed(() => (minOrder.value ? Math.min(100, (subtotal.value / minOrder.value) * 100) : 100));
const meetsMinOrder = computed(() => !minOrder.value || subtotal.value >= minOrder.value);
const minOrderRemaining = computed(() => Math.max(0, minOrder.value - subtotal.value));

const orderEstimateMinutes = computed(() =>
    estimateOrderMinutes({
        orderType: props.orderType,
        cart: props.cart,
        branch: props.branch,
        productsById: props.productsById,
    }),
);

const orderEstimateLabel = computed(() => {
    const formatted = formatEstimateMinutes(orderEstimateMinutes.value);
    if (!formatted) {
        return '';
    }

    if (props.orderType === 'delivery') {
        return t('cart.estimated_delivery').replace(':time', formatted);
    }

    if (props.orderType === 'pickup') {
        return t('cart.estimated_pickup').replace(':time', formatted);
    }

    return '';
});

const needsGeo = computed(
    () =>
        props.orderType === 'delivery' &&
        props.branch.delivery_radius_km &&
        props.branch.latitude &&
        props.branch.longitude,
);

const page = usePage();
const t = (key) => page.props.translations?.[key] ?? key;

const formatPrice = (value) =>
    value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const showPixOnline = computed(() => props.paymentSettings?.pix_enabled && props.paymentSettings?.pix_key);
const showCardOnline = computed(() => props.paymentSettings?.card_online_enabled);

const productName = (item) => item.name.split(' (')[0];

const disposableItems = computed(() => normalizeOrderDisposables(props.branch.order_disposables));

const setDisposableQty = (item, qty) => {
    emit('update:orderDisposables', {
        ...props.orderDisposables,
        [item.key]: clampDisposableQty(item, qty),
    });
};

const groupedVariations = (item) => {
    const v = item.variations ?? [];
    return {
        choice: v.filter((x) => !x.type || x.type === 'choice'),
        addon: v.filter((x) => x.type === 'addon'),
        disposable: v.filter((x) => x.type === 'disposable'),
    };
};

const updateAddress = (field, value) => {
    emit('update:deliveryAddress', { ...props.deliveryAddress, [field]: value });
};

const applyAddress = (address, { lock = false } = {}) => {
    const next = { ...props.deliveryAddress, ...address };
    if (next.postal_code) {
        next.postal_code = formatCepInput(next.postal_code);
    }
    emit('update:deliveryAddress', next);
    if (lock) {
        addressLocked.value = true;
    }
};

const syncCoordsForRadius = async () => {
    if (!needsGeo.value || deliveryLat.value !== null) {
        return;
    }
    const a = props.deliveryAddress;
    if (!a.street?.trim() || !a.city?.trim()) {
        return;
    }
    const geo = await forwardGeocode(a);
    if (geo.ok) {
        deliveryLat.value = geo.lat;
        deliveryLng.value = geo.lng;
        geoError.value = '';
    }
};

const lookupCep = async (digits) => {
    if (!isValidCep(digits)) {
        return;
    }

    cepLoading.value = true;
    cepError.value = '';
    cepMessage.value = '';
    geoError.value = '';

    const result = await resolveDeliveryAddress(digits);

    cepLoading.value = false;

    if (result.ok) {
        applyAddress(result.address, { lock: result.source === 'viacep' });
        if (result.lat != null && result.lng != null) {
            deliveryLat.value = result.lat;
            deliveryLng.value = result.lng;
        }
        cepMessage.value =
            result.source === 'viacep'
                ? 'Endereço preenchido pelo CEP.'
                : 'CEP não encontrado — endereço preenchido pela sua localização.';
        await syncCoordsForRadius();
        return;
    }

    cepError.value = result.error;
    if (result.fallbackError) {
        geoError.value = result.fallbackError;
    }
    if (result.lat != null && result.lng != null) {
        deliveryLat.value = result.lat;
        deliveryLng.value = result.lng;
    }
};

const scheduleCepLookup = (formatted) => {
    if (cepLookupTimer) {
        clearTimeout(cepLookupTimer);
        cepLookupTimer = null;
    }

    if (!isValidCep(formatted)) {
        addressLocked.value = false;
        return;
    }

    cepLookupTimer = setTimeout(() => {
        lookupCep(normalizeCep(formatted));
    }, 400);
};

const onCepInput = (event) => {
    const input = event.target;
    const { value: formatted, selectionStart, selectionEnd } = formatCepWithSelection(
        input.value,
        input.selectionStart ?? input.value.length,
    );

    updateAddress('postal_code', formatted);
    cepError.value = '';
    cepMessage.value = '';

    nextTick(() => {
        if (cepInputRef.value) {
            cepInputRef.value.setSelectionRange(selectionStart, selectionEnd);
        }
    });

    scheduleCepLookup(formatted);
};

const onCepKeydown = (event) => {
    const allowed = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
    if (allowed.includes(event.key) || event.ctrlKey || event.metaKey) {
        return;
    }
    if (!/^\d$/.test(event.key)) {
        event.preventDefault();
    }
};

const requestLocationOnly = async () => {
    geoError.value = '';
    cepError.value = '';
    locating.value = true;

    const position = await getBrowserPosition();
    if (!position.ok) {
        geoError.value = position.error;
        locating.value = false;
        return;
    }

    deliveryLat.value = position.lat;
    deliveryLng.value = position.lng;

    const reversed = await reverseGeocode(position.lat, position.lng);
    locating.value = false;

    if (!reversed.ok) {
        geoError.value = reversed.error;
        cepMessage.value = 'Localização obtida. Complete o endereço manualmente.';
        return;
    }

    applyAddress(reversed.address);
    cepMessage.value = 'Endereço preenchido pela sua localização.';
};

watch(
    () => props.cart.length,
    (n) => {
        if (n === 0) step.value = 'items';
    },
);

watch(
    () => props.deliveryAddress.postal_code,
    (value) => {
        if (!value) {
            return;
        }
        const formatted = formatCepInput(value);
        if (formatted !== value) {
            updateAddress('postal_code', formatted);
        }
    },
    { immediate: true },
);

watch(
    () => props.orderType,
    (type) => {
        if (
            type === 'delivery' &&
            isValidCep(props.deliveryAddress.postal_code) &&
            !props.deliveryAddress.street?.trim() &&
            !cepLoading.value
        ) {
            lookupCep(normalizeCep(props.deliveryAddress.postal_code));
        }
    },
);

watch(
    () => [
        props.deliveryAddress.street,
        props.deliveryAddress.number,
        props.deliveryAddress.city,
        props.orderType,
    ],
    async () => {
        if (props.orderType === 'delivery' && needsGeo.value && deliveryLat.value === null) {
            await syncCoordsForRadius();
        }
    },
);

const showAddressFields = computed(
    () =>
        isValidCep(props.deliveryAddress.postal_code) ||
        addressLocked.value ||
        Boolean(props.deliveryAddress.street?.trim()),
);

const missingFields = computed(() => {
    const list = [];
    if (!props.guestName?.trim()) list.push('nome');
    if (!props.guestPhone?.trim()) list.push('telefone');
    if (!meetsMinOrder.value) list.push('pedido mínimo');
    if (props.orderType === 'delivery') {
        const a = props.deliveryAddress;
        if (!isValidCep(a.postal_code)) list.push('CEP');
        if (!a.street?.trim()) list.push('rua');
        if (!a.number?.trim()) list.push('número');
        if (!a.neighborhood?.trim()) list.push('bairro');
        if (!a.city?.trim()) list.push('cidade');
        if (needsGeo.value && deliveryLat.value === null) list.push('localização');
    }
    return list;
});

const canSubmit = computed(() => missingFields.value.length === 0 && props.cart.length > 0);

const goCheckout = () => {
    if (!props.canCheckout || !props.cart.length) return;
    if (!meetsMinOrder.value) return;
    step.value = 'checkout';
};

watch(
    () => props.canCheckout,
    (ok) => {
        if (!ok && step.value === 'checkout') {
            step.value = 'items';
        }
    },
);

const goItems = () => {
    step.value = 'items';
};

const submitHint = computed(() => {
    if (props.processing) return 'Enviando...';
    if (!meetsMinOrder.value) return `Faltam ${formatPrice(minOrderRemaining.value)} para o mínimo`;
    if (missingFields.value.length) return 'Preencha os campos obrigatórios';
    return `Finalizar · ${formatPrice(displayTotal.value)}`;
});

const minScheduleLocal = computed(() => {
    const d = new Date();
    d.setMinutes(d.getMinutes() + 30);
    return d.toISOString().slice(0, 16);
});

defineExpose({ deliveryLat, deliveryLng });
</script>

<template>
    <div
        class="flex min-h-0 flex-col bg-white"
        :class="
            compact
                ? 'h-full max-h-[92dvh]'
                : 'max-h-[calc(100vh-6rem)] rounded-2xl border border-stone-200 shadow-menu-lg lg:sticky lg:top-24'
        "
    >
        <!-- Header -->
        <header class="shrink-0 border-b border-stone-100 px-4 pb-3 pt-1 lg:px-5 lg:pt-4">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-semibold text-stone-900">Sacola</h2>
                    <p v-if="cart.length" class="text-xs text-stone-500">
                        {{ itemCount }} {{ itemCount === 1 ? 'item' : 'itens' }} · {{ formatPrice(subtotal) }}
                    </p>
                    <p v-else class="text-xs text-stone-500">Seu pedido aparece aqui</p>
                </div>
                <button
                    v-if="compact"
                    type="button"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-stone-100 text-stone-600"
                    aria-label="Fechar sacola"
                    @click="emit('close')"
                >
                    ✕
                </button>
            </div>

            <!-- Steps -->
            <div v-if="cart.length && canCheckout" class="mt-3 flex gap-1 rounded-xl bg-stone-100 p-1">
                <button
                    type="button"
                    class="flex-1 rounded-lg py-2 text-xs font-semibold transition"
                    :class="step === 'items' ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-500'"
                    @click="goItems"
                >
                    1. Itens
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-lg py-2 text-xs font-semibold transition"
                    :class="step === 'checkout' ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-500'"
                    @click="goCheckout"
                >
                    2. Entrega e pagamento
                </button>
            </div>

            <!-- Min order -->
            <div v-if="minOrder > 0 && cart.length" class="mt-3">
                <div class="flex justify-between text-[11px]">
                    <span
                        :class="[
                            'inline-flex items-center gap-1',
                            meetsMinOrder ? 'font-medium text-emerald-700' : 'font-medium text-amber-800',
                        ]"
                    >
                        <NavIcon v-if="meetsMinOrder" name="check-simple" size="sm" class="text-emerald-600" />
                        {{
                            meetsMinOrder ? 'Pedido mínimo ok' : `Faltam ${formatPrice(minOrderRemaining)}`
                        }}
                    </span>
                    <span class="text-stone-400">mín. {{ formatPrice(minOrder) }}</span>
                </div>
                <div class="mt-1 h-1 overflow-hidden rounded-full bg-stone-200">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :style="{
                            width: `${minOrderProgress}%`,
                            backgroundColor: meetsMinOrder ? '#10b981' : 'var(--menu-primary)',
                        }"
                    />
                </div>
            </div>
        </header>

        <!-- Empty -->
        <div v-if="!cart.length" class="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center">
            <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-stone-100 text-stone-400">
                <NavIcon name="cart" size="xl" />
            </div>
            <p class="font-medium text-stone-800">Sacola vazia</p>
            <p class="mt-1 max-w-[14rem] text-sm text-stone-500">Escolha itens no cardápio e toque em Adicionar.</p>
        </div>

        <!-- Step: items -->
        <div v-else-if="step === 'items'" class="flex min-h-0 flex-1 flex-col">
            <ul class="min-h-0 flex-1 space-y-2 overflow-y-auto px-4 py-3 lg:px-5">
                <li
                    v-for="item in cart"
                    :key="item.line_key || item.product_id"
                    class="flex gap-3 rounded-2xl border border-stone-100 bg-stone-50/80 p-3"
                >
                    <div class="flex min-w-0 flex-1 flex-col">
                        <p class="text-sm font-semibold leading-tight text-stone-900">{{ productName(item) }}</p>
                        <div v-if="item.variations?.length" class="mt-1.5 space-y-1">
                            <p v-if="groupedVariations(item).choice.length" class="text-xs text-stone-600">
                                {{ groupedVariations(item).choice.map((x) => x.option_name).join(' · ') }}
                            </p>
                            <p v-if="groupedVariations(item).addon.length" class="text-xs text-stone-600">
                                <span class="font-medium text-orange-700">+</span>
                                {{ groupedVariations(item).addon.map((x) => x.option_name).join(', ') }}
                            </p>
                            <p v-if="groupedVariations(item).disposable.length" class="text-xs text-stone-500">
                                {{ groupedVariations(item).disposable.map((x) => formatVariationLabel(x)).join(', ') }}
                            </p>
                        </div>
                        <p v-else class="mt-0.5 text-xs text-stone-500">{{ formatPrice(item.unit_price) }} un.</p>
                        <p class="mt-1.5 text-sm font-bold text-stone-900">
                            {{ formatPrice(item.unit_price * item.quantity) }}
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-col items-center justify-between gap-1">
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-lg font-medium text-stone-700 shadow-sm ring-1 ring-stone-200 active:scale-95"
                            aria-label="Menos"
                            @click="emit('decrement', item)"
                        >
                            −
                        </button>
                        <span class="text-sm font-bold tabular-nums text-stone-900">{{ item.quantity }}</span>
                        <button
                            type="button"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-lg font-medium text-white shadow-sm active:scale-95"
                            style="background-color: var(--menu-primary)"
                            aria-label="Mais"
                            @click="emit('increment', item)"
                        >
                            +
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Step: checkout -->
        <div v-else-if="step === 'checkout' && canCheckout" class="min-h-0 flex-1 overflow-y-auto px-4 py-3 lg:px-5">
            <button type="button" class="mb-3 text-xs font-medium text-stone-500 hover:text-stone-800" @click="goItems">
                ← Voltar aos itens
            </button>

            <section class="space-y-4">
                <div class="rounded-2xl border border-stone-100 bg-stone-50 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-500">Seus dados</p>
                    <div class="space-y-2">
                        <input
                            :value="guestName"
                            class="menu-input !bg-white !py-2.5"
                            placeholder="Nome completo *"
                            autocomplete="name"
                            @input="emit('update:guestName', $event.target.value)"
                        />
                        <input
                            :value="guestPhone"
                            class="menu-input !bg-white !py-2.5"
                            placeholder="WhatsApp com DDD *"
                            autocomplete="tel"
                            inputmode="tel"
                            @input="emit('update:guestPhone', $event.target.value)"
                        />
                        <input
                            v-if="showGuestEmail"
                            :value="guestEmail"
                            type="email"
                            class="menu-input !bg-white !py-2.5"
                            placeholder="E-mail (opcional — enviamos o código de acesso)"
                            autocomplete="email"
                            @input="emit('update:guestEmail', $event.target.value)"
                        />
                    </div>
                </div>

                <div
                    v-if="isDineIn"
                    class="rounded-2xl border border-orange-200 bg-orange-50 p-3 text-sm text-orange-900"
                >
                    <p class="font-semibold">Pedido no salão</p>
                    <p v-if="table?.name" class="mt-0.5">Mesa {{ table.name }} — o pedido vai direto para a cozinha.</p>
                </div>

                <div v-else class="rounded-2xl border border-stone-100 bg-stone-50 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-500">Recebimento</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            v-if="branch.pickup_available"
                            type="button"
                            class="flex flex-col items-center gap-1 rounded-xl border-2 py-3 text-sm font-semibold transition"
                            :class="orderType === 'pickup' ? 'border-transparent text-white' : 'border-stone-200 bg-white text-stone-600'"
                            :style="orderType === 'pickup' ? { backgroundColor: 'var(--menu-primary)' } : {}"
                            @click="emit('update:orderType', 'pickup')"
                        >
                            <NavIcon name="store" size="lg" />
                            Retirada
                        </button>
                        <button
                            v-if="branch.delivery_available"
                            type="button"
                            class="flex flex-col items-center gap-1 rounded-xl border-2 py-3 text-sm font-semibold transition"
                            :class="orderType === 'delivery' ? 'border-transparent text-white' : 'border-stone-200 bg-white text-stone-600'"
                            :style="orderType === 'delivery' ? { backgroundColor: 'var(--menu-primary)' } : {}"
                            @click="emit('update:orderType', 'delivery')"
                        >
                            <NavIcon name="moto" size="lg" />
                            Entrega
                        </button>
                    </div>
                    <p
                        v-if="orderEstimateLabel && orderType === 'delivery'"
                        class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-sm font-medium text-emerald-900"
                    >
                        {{ orderEstimateLabel }}
                    </p>
                    <p
                        v-else-if="orderEstimateLabel && orderType === 'pickup'"
                        class="mt-3 rounded-xl border border-stone-200 bg-white px-3 py-2 text-center text-sm font-medium text-stone-700"
                    >
                        {{ orderEstimateLabel }}
                    </p>
                </div>

                <div class="rounded-2xl border border-stone-100 bg-stone-50 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-500">{{ t('cart.payment') }}</p>
                    <div class="space-y-2">
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm">
                            <input
                                type="radio"
                                name="payment_method"
                                value="on_delivery"
                                :checked="paymentMethod === 'on_delivery'"
                                @change="emit('update:paymentMethod', 'on_delivery')"
                            />
                            {{ t('payment.on_delivery') }}
                        </label>
                        <div v-if="paymentMethod === 'on_delivery'" class="ml-6 flex flex-wrap gap-2">
                            <button
                                v-for="ch in ['pix', 'cash', 'card', 'debit']"
                                :key="ch"
                                type="button"
                                class="rounded-lg border px-2 py-1 text-xs capitalize"
                                :class="paymentChannel === ch ? 'border-orange-500 bg-orange-50' : 'border-stone-200'"
                                @click="emit('update:paymentChannel', ch)"
                            >
                                {{ t(`payment.channel.${ch}`) }}
                            </button>
                        </div>
                        <label
                            v-if="showPixOnline"
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm"
                        >
                            <input
                                type="radio"
                                name="payment_method"
                                value="pix_online"
                                :checked="paymentMethod === 'pix_online'"
                                @change="emit('update:paymentMethod', 'pix_online')"
                            />
                            {{ t('payment.pix_online') }}
                        </label>
                        <label
                            v-if="showCardOnline"
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm"
                        >
                            <input
                                type="radio"
                                name="payment_method"
                                value="card_online"
                                :checked="paymentMethod === 'card_online'"
                                @change="emit('update:paymentMethod', 'card_online')"
                            />
                            {{ t('payment.card_online') }}
                        </label>
                    </div>
                </div>

                <div v-if="orderType === 'delivery'" class="rounded-2xl border border-stone-100 bg-stone-50 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-500">Endereço de entrega</p>
                    <div class="space-y-2">
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-stone-600">CEP *</label>
                            <input
                                ref="cepInputRef"
                                :value="deliveryAddress.postal_code"
                                class="menu-input !bg-white !py-2.5 font-mono tracking-wide"
                                placeholder="00000-000"
                                type="text"
                                inputmode="numeric"
                                autocomplete="postal-code"
                                maxlength="9"
                                :disabled="cepLoading || locating"
                                @keydown="onCepKeydown"
                                @input="onCepInput"
                            />
                        </div>
                        <p v-if="cepMessage" class="text-xs text-emerald-700">{{ cepMessage }}</p>
                        <p v-if="cepError" class="text-xs text-amber-800">{{ cepError }}</p>

                        <template v-if="showAddressFields">
                            <input
                                :value="deliveryAddress.street"
                                class="menu-input !bg-white !py-2.5"
                                placeholder="Rua *"
                                @input="updateAddress('street', $event.target.value)"
                            />
                            <div class="grid grid-cols-5 gap-2">
                                <input
                                    :value="deliveryAddress.number"
                                    class="menu-input col-span-2 !bg-white !py-2.5"
                                    placeholder="Nº *"
                                    @input="updateAddress('number', $event.target.value)"
                                />
                                <input
                                    :value="deliveryAddress.complement"
                                    class="menu-input col-span-3 !bg-white !py-2.5"
                                    placeholder="Complemento"
                                    @input="updateAddress('complement', $event.target.value)"
                                />
                            </div>
                            <input
                                :value="deliveryAddress.neighborhood"
                                class="menu-input !bg-white !py-2.5"
                                placeholder="Bairro *"
                                @input="updateAddress('neighborhood', $event.target.value)"
                            />
                            <div class="grid grid-cols-3 gap-2">
                                <input
                                    :value="deliveryAddress.city"
                                    class="menu-input col-span-2 !bg-white !py-2.5"
                                    placeholder="Cidade *"
                                    @input="updateAddress('city', $event.target.value)"
                                />
                                <input
                                    :value="deliveryAddress.state"
                                    class="menu-input !bg-white !py-2.5 uppercase"
                                    placeholder="UF"
                                    maxlength="2"
                                    @input="updateAddress('state', $event.target.value.toUpperCase())"
                                />
                            </div>
                        </template>

                        <div class="flex flex-wrap gap-2 pt-1">
                            <button
                                v-if="!cepLoading && !locating"
                                type="button"
                                class="text-xs font-semibold text-stone-600 underline decoration-stone-300 hover:text-stone-900"
                                @click="lookupCep(normalizeCep(deliveryAddress.postal_code))"
                            >
                                Buscar CEP novamente
                            </button>
                            <button
                                type="button"
                                class="text-xs font-semibold underline decoration-stone-300 hover:text-stone-900"
                                :style="{ color: 'var(--menu-primary)' }"
                                :disabled="locating"
                                @click="requestLocationOnly"
                            >
                                {{ locating ? 'Buscando localização...' : 'Usar minha localização' }}
                            </button>
                        </div>

                        <button
                            v-if="needsGeo"
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-semibold"
                            :class="
                                deliveryLat !== null
                                    ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200'
                                    : 'bg-white text-stone-700 ring-1 ring-stone-200'
                            "
                            :disabled="locating || cepLoading"
                            @click="requestLocationOnly"
                        >
                            <template v-if="deliveryLat !== null">
                                <NavIcon name="check-simple" size="sm" />
                                Localização confirmada
                            </template>
                            <template v-else-if="locating">Obtendo localização...</template>
                            <template v-else>
                                <NavIcon name="map-pin" size="sm" />
                                Confirmar localização no mapa *
                            </template>
                        </button>
                        <p v-if="geoError" class="text-xs text-red-600">{{ geoError }}</p>
                    </div>
                </div>

                <div
                    v-if="disposableItems.length"
                    class="rounded-2xl border border-stone-100 bg-stone-50 p-3"
                >
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-500">Descartáveis do pedido</p>
                    <div class="space-y-1.5">
                        <div
                            v-for="item in disposableItems"
                            :key="item.key"
                            class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2.5 ring-1 ring-stone-100"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-stone-800">{{ item.label }}</p>
                                <p v-if="item.min_qty > 0" class="text-[10px] text-orange-600">Mín. {{ item.min_qty }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <button
                                    type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-stone-100 text-base font-medium text-stone-700"
                                    :disabled="(orderDisposables[item.key] ?? 0) <= item.min_qty"
                                    @click="setDisposableQty(item, (orderDisposables[item.key] ?? 0) - 1)"
                                >
                                    −
                                </button>
                                <span class="min-w-[1.25rem] text-center text-sm font-bold tabular-nums">{{ orderDisposables[item.key] ?? 0 }}</span>
                                <button
                                    type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-base font-medium text-white"
                                    style="background-color: var(--menu-primary)"
                                    :disabled="(orderDisposables[item.key] ?? 0) >= item.max_qty"
                                    @click="setDisposableQty(item, (orderDisposables[item.key] ?? 0) + 1)"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-dashed border-stone-200 bg-white p-3">
                    <p class="mb-2 text-xs font-semibold text-stone-500">Cupom</p>
                    <input
                        :value="couponCode"
                        class="menu-input !bg-stone-50 uppercase"
                        placeholder="Código promocional"
                        @input="emit('update:couponCode', $event.target.value)"
                    />
                </div>

                <div class="rounded-2xl border border-stone-100 bg-stone-50 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-500">Gorjeta (opcional)</p>
                    <input
                        :value="tipAmount"
                        type="number"
                        min="0"
                        step="1"
                        class="menu-input !bg-white !py-2.5"
                        placeholder="R$ 0,00"
                        @input="emit('update:tipAmount', $event.target.value)"
                    />
                </div>

                <div v-if="branch.allow_scheduled_orders" class="rounded-2xl border border-stone-100 bg-stone-50 p-3">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-500">Agendar pedido</p>
                    <input
                        :value="scheduledFor"
                        type="datetime-local"
                        :min="minScheduleLocal"
                        class="menu-input !bg-white !py-2.5"
                        @input="emit('update:scheduledFor', $event.target.value)"
                    />
                    <p class="mt-1 text-[11px] text-stone-500">Deixe vazio para pedido imediato.</p>
                </div>

                <div class="rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-900">
                    Pagamento na entrega/retirada. O restaurante confirma o recebimento depois.
                </div>
            </section>
        </div>

        <!-- Footer fixo -->
        <footer
            v-if="cart.length"
            class="shrink-0 border-t border-stone-100 bg-white px-4 py-3 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] lg:px-5"
        >
            <p
                v-if="orderEstimateLabel"
                class="mb-2 text-center text-xs font-medium text-emerald-800"
            >
                {{ orderEstimateLabel }}
            </p>
            <div class="mb-2 space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-stone-500">Subtotal</span>
                    <span class="font-medium text-stone-900">{{ formatPrice(subtotal) }}</span>
                </div>
                <div v-if="tipValue > 0" class="flex justify-between">
                    <span class="text-stone-500">Gorjeta</span>
                    <span class="font-medium text-stone-900">{{ formatPrice(tipValue) }}</span>
                </div>
                <div class="flex justify-between border-t border-stone-100 pt-1">
                    <span class="font-semibold text-stone-700">Total estimado</span>
                    <span class="text-lg font-bold text-stone-900">{{ formatPrice(displayTotal) }}</span>
                </div>
            </div>

            <template v-if="!canCheckout">
                <p class="mb-3 text-center text-xs text-amber-900">
                    {{ t('branch.guest_checkout_disabled') }}
                </p>
                <Link v-if="loginUrl" :href="loginUrl" class="menu-btn-primary block w-full !py-3.5 text-center">
                    Entrar ou criar conta
                </Link>
            </template>

            <template v-else>
                <button
                    v-if="step === 'items'"
                    type="button"
                    class="menu-btn-primary w-full !py-3.5"
                    :disabled="!meetsMinOrder"
                    @click="goCheckout"
                >
                    {{ meetsMinOrder ? 'Continuar para entrega' : `Faltam ${formatPrice(minOrderRemaining)}` }}
                </button>
                <button
                    v-else
                    type="button"
                    class="menu-btn-primary w-full !py-3.5"
                    :disabled="processing || !canSubmit"
                    @click="emit('checkout')"
                >
                    {{ submitHint }}
                </button>

                <p
                    v-if="step === 'checkout' && missingFields.length && !processing"
                    class="mt-2 text-center text-[11px] text-amber-700"
                >
                    Falta: {{ missingFields.join(', ') }}
                </p>
            </template>
        </footer>
    </div>
</template>
