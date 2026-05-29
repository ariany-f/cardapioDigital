<script setup>
import NavIcon from '@/Components/NavIcon.vue';
import SeoHead from '@/Components/SeoHead.vue';
import ChatWidget from '@/Components/Public/ChatWidget.vue';
import MenuCart from '@/Components/Public/MenuCart.vue';
import MenuProductCard from '@/Components/Public/MenuProductCard.vue';
import BranchMapModal from '@/Components/Maps/BranchMapModal.vue';
import MenuProductModal from '@/Components/Public/MenuProductModal.vue';
import PublicMenuLayout from '@/Layouts/PublicMenuLayout.vue';
import { markChatPurchasedLocal } from '@/composables/useChatEligibility';
import {
    clearBranchSession,
    loadBranchSession,
    saveBranchSession,
} from '@/composables/useMenuSession';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

defineOptions({ layout: PublicMenuLayout });

const props = defineProps({
    seo: Object,
    branch: Object,
    banners: Array,
    featured: { type: Array, default: () => [] },
    combos: { type: Array, default: () => [] },
    categories: Array,
    tenantSlug: String,
    publicUrl: String,
    table: { type: Object, default: null },
    defaultOrderType: { type: String, default: '' },
    reorderCart: { type: Array, default: () => [] },
    paymentSettings: { type: Object, default: () => ({}) },
    chatAvailable: { type: Boolean, default: false },
    chatGuestProfile: { type: Object, default: null },
});

const isTableOrder = computed(() => !!props.table);
const initialOrderType = computed(
    () => props.defaultOrderType || (props.branch?.delivery_available ? 'delivery' : 'pickup'),
);

const modalProduct = ref(null);
const modalOpen = ref(false);

const page = usePage();
const t = (key) => page.props.translations?.[key] ?? key;
const errors = computed(() => page.props.errors ?? {});

const restored = loadBranchSession(props.tenantSlug, props.branch.slug, props.branch, {
    defaultOrderType: initialOrderType.value,
    lockOrderType: isTableOrder.value,
});

const loggedCustomer = computed(() => page.props.auth?.customer);

const cart = ref(props.reorderCart?.length ? props.reorderCart : restored.cart);
const guestName = ref(loggedCustomer.value?.name || restored.guestName);
const guestPhone = ref(loggedCustomer.value?.phone || restored.guestPhone);
const guestEmail = ref(loggedCustomer.value?.email || restored.guestEmail || '');
const orderType = ref(restored.orderType);
const couponCode = ref(restored.couponCode);
const deliveryAddress = ref(restored.deliveryAddress);
const orderDisposables = ref(restored.orderDisposables);
const tipAmount = ref(restored.tipAmount ?? '');
const paymentMethod = ref(restored.paymentMethod ?? 'on_delivery');
const paymentChannel = ref(restored.paymentChannel ?? 'pix');
const scheduledFor = ref(restored.scheduledFor ?? '');

const persistSession = () => {
    saveBranchSession(props.tenantSlug, props.branch.slug, {
        cart: cart.value,
        guestName: guestName.value,
        guestPhone: guestPhone.value,
        guestEmail: guestEmail.value,
        orderType: orderType.value,
        couponCode: couponCode.value,
        deliveryAddress: deliveryAddress.value,
        orderDisposables: orderDisposables.value,
        tipAmount: tipAmount.value,
        scheduledFor: scheduledFor.value,
        paymentMethod: paymentMethod.value,
        paymentChannel: paymentChannel.value,
    });
};

watch([cart, guestName, guestPhone, guestEmail, orderType, couponCode, deliveryAddress, orderDisposables, tipAmount, scheduledFor, paymentMethod, paymentChannel], persistSession, {
    deep: true,
});
const activeCategory = ref(props.categories?.[0]?.id ?? null);
const searchQuery = ref('');

const catalogProductsById = computed(() => {
    const map = new Map();
    for (const product of props.featured ?? []) {
        map.set(product.id, product);
    }
    for (const category of props.categories ?? []) {
        for (const product of category.products ?? []) {
            map.set(product.id, product);
        }
    }
    return map;
});

const filteredCategories = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.categories;
    return props.categories
        .map((cat) => ({
            ...cat,
            products: cat.products.filter(
                (p) =>
                    p.name.toLowerCase().includes(q) ||
                    (p.description && p.description.toLowerCase().includes(q)) ||
                    (p.tags && p.tags.some((tag) => tag.toLowerCase().includes(q))),
            ),
        }))
        .filter((cat) => cat.products.length > 0);
});
const mobileCartOpen = ref(false);
const mapOpen = ref(false);
const googleMaps = computed(() => page.props.googleMaps);
const showMapButton = computed(
    () => googleMaps.value?.api_key && props.branch.latitude != null && props.branch.longitude != null,
);
const processing = ref(false);
const desktopCartRef = ref(null);
const mobileCartRef = ref(null);
const catalogScrollRef = ref(null);

const branchOpen = computed(() => props.branch?.can_order === true);
const guestCheckoutEnabled = computed(() => props.branch?.guest_checkout_enabled !== false);

/** Montar sacola enquanto a loja aceita pedidos. */
const canAddToCart = computed(() => branchOpen.value);

/** Finalizar pedido: cliente logado, mesa QR ou checkout de visitante habilitado. */
const canCheckout = computed(() => {
    if (!branchOpen.value) return false;
    if (loggedCustomer.value || isTableOrder.value) return true;
    return guestCheckoutEnabled.value;
});

const loginUrl = computed(() =>
    route('tenant.conta.login', {
        tenant: props.tenantSlug,
        redirect: window.location.pathname + window.location.search,
    }),
);

const cartItemCount = computed(() => cart.value.reduce((s, i) => s + i.quantity, 0));

const cartTotal = computed(() =>
    cart.value.reduce((sum, item) => sum + item.unit_price * item.quantity, 0),
);

const lineKey = (productId, variations = []) => {
    const ids = variations
        .map((v) => `${v.option_id}:${v.quantity ?? 1}`)
        .sort()
        .join('-');
    return `${productId}:${ids}`;
};

const getQty = (productId) =>
    cart.value.filter((i) => i.product_id === productId).reduce((s, i) => s + i.quantity, 0);

const addLine = (product, unitPrice, variations = []) => {
    const key = lineKey(product.id, variations);
    const label =
        variations.length > 0
            ? `${product.name} (${variations.map((v) => (v.quantity > 1 ? `${v.option_name} ×${v.quantity}` : v.option_name)).join(', ')})`
            : product.name;
    const existing = cart.value.find((i) => i.line_key === key);
    if (existing) existing.quantity++;
    else {
        cart.value.push({
            line_key: key,
            product_id: product.id,
            name: label,
            unit_price: unitPrice,
            quantity: 1,
            variations,
        });
    }
};

const addCombo = (combo) => {
    if (!canAddToCart.value) return;
    const key = `combo-${combo.id}`;
    const existing = cart.value.find((i) => i.line_key === key);
    if (existing) existing.quantity++;
    else {
        cart.value.push({
            line_key: key,
            combo_id: combo.id,
            name: combo.name,
            unit_price: parseFloat(combo.price),
            quantity: 1,
            variations: [],
        });
    }
};

const requestAdd = (product) => {
    if (!canAddToCart.value || product.out_of_stock) return;
    if (product.has_customization || product.has_variations) {
        modalProduct.value = product;
        modalOpen.value = true;
        return;
    }
    addLine(product, parseFloat(product.base_price), []);
};

const onModalConfirm = ({ product, unit_price, variations }) => addLine(product, unit_price, variations);

const increment = (product) => requestAdd(product);

const decrementProduct = (product) => {
    const lines = cart.value.filter((i) => i.product_id === product.id);
    if (!lines.length) return;
    const last = lines[lines.length - 1];
    if (last.quantity <= 1) cart.value = cart.value.filter((i) => i.line_key !== last.line_key);
    else last.quantity--;
};

const incrementItem = (item) => {
    const found = cart.value.find((i) => i.line_key === item.line_key);
    if (found) found.quantity++;
};

const decrementItem = (item) => {
    const found = cart.value.find((i) => i.line_key === item.line_key);
    if (!found) return;
    if (found.quantity <= 1) cart.value = cart.value.filter((i) => i.line_key !== item.line_key);
    else found.quantity--;
};

const usesCatalogScrollPane = () =>
    typeof window !== 'undefined' && window.matchMedia('(min-width: 1024px)').matches;

const scrollToCategory = (id) => {
    activeCategory.value = id;
    const el = document.getElementById(`category-${id}`);
    if (!el) return;

    const root = catalogScrollRef.value;
    if (usesCatalogScrollPane() && root) {
        const top = el.getBoundingClientRect().top - root.getBoundingClientRect().top + root.scrollTop;
        root.scrollTo({ top: Math.max(0, top - 12), behavior: 'smooth' });
        return;
    }

    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

let observer;

const bindCategoryObserver = () => {
    observer?.disconnect();

    const sections = (searchQuery.value.trim() ? filteredCategories.value : props.categories)
        .map((c) => document.getElementById(`category-${c.id}`))
        .filter(Boolean);

    if (!sections.length) return;

    const root = usesCatalogScrollPane() ? catalogScrollRef.value : null;

    observer = new IntersectionObserver(
        (entries) => {
            const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio);
            if (visible[0]) {
                const id = Number(visible[0].target.id.replace('category-', ''));
                if (id) activeCategory.value = id;
            }
        },
        {
            root,
            rootMargin: root ? '-8% 0px -55% 0px' : '-30% 0px -55% 0px',
            threshold: [0, 0.25, 0.5],
        },
    );

    sections.forEach((el) => observer.observe(el));
};

onMounted(() => nextTick(() => bindCategoryObserver()));

watch([filteredCategories, searchQuery], () => nextTick(() => bindCategoryObserver()));

onUnmounted(() => observer?.disconnect());

watch(cartItemCount, (n) => {
    if (n === 0) mobileCartOpen.value = false;
});

const checkout = () => {
    if (!canCheckout.value || !cart.value.length) return;
    if (!loggedCustomer.value && (!guestName.value?.trim() || !guestPhone.value?.trim())) return;
    processing.value = true;

    const cartRef = mobileCartOpen.value ? mobileCartRef.value : desktopCartRef.value;
    const payload = {
        branch_slug: props.branch.slug,
        guest_name: guestName.value,
        guest_phone: guestPhone.value,
        guest_email: guestEmail.value?.trim() || null,
        type: orderType.value,
        coupon_code: couponCode.value || null,
        items: cart.value,
        order_disposables: orderDisposables.value,
        tip_amount: tipAmount.value ? parseFloat(tipAmount.value) : 0,
        scheduled_for: scheduledFor.value || null,
        payment_method: paymentMethod.value,
        payment_channel: paymentMethod.value === 'on_delivery' ? paymentChannel.value : null,
    };

    if (orderType.value === 'dine_in' && props.table?.id) {
        payload.table_id = props.table.id;
    }

    if (orderType.value === 'delivery') {
        payload.delivery_address = deliveryAddress.value;
        if (cartRef?.deliveryLat != null) {
            payload.delivery_lat = cartRef.deliveryLat;
            payload.delivery_lng = cartRef.deliveryLng;
        }
    }

    router.post(route('tenant.checkout', { tenant: props.tenantSlug }), payload, {
        onFinish: () => (processing.value = false),
        onSuccess: () => {
            cart.value = [];
            couponCode.value = '';
            markChatPurchasedLocal(props.tenantSlug, props.branch.slug);
            clearBranchSession(props.tenantSlug, props.branch.slug);
            persistSession();
            mobileCartOpen.value = false;
        },
    });
};

const formatPrice = (value) =>
    value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const chatEnabled = computed(() => props.chatAvailable);
</script>

<template>
    <SeoHead :seo="seo" />

    <!-- Hero -->
    <section class="relative h-52 overflow-hidden sm:h-60 lg:h-72">
        <div class="absolute inset-0">
            <img
                v-if="branch.cover_url"
                :src="branch.cover_url"
                alt=""
                class="h-full w-full object-cover"
            />
            <div
                v-else
                class="h-full w-full bg-gradient-to-br from-stone-800 via-stone-700 to-stone-900"
                :style="{
                    backgroundImage: `linear-gradient(135deg, color-mix(in srgb, var(--menu-primary) 55%, #1a1a1a), #1a1a1a)`,
                }"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/35 to-black/20" />
        </div>

        <div class="relative mx-auto max-w-7xl px-4 pb-8 pt-10 text-white lg:px-8 lg:pb-12 lg:pt-14">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-widest text-white/80">Cardápio digital</p>
                    <h1 class="mt-1 text-3xl font-bold leading-tight lg:text-4xl">
                        {{ branch.name }}
                    </h1>
                    <p v-if="table" class="mt-2 inline-flex rounded-full bg-white/20 px-3 py-1 text-sm font-medium backdrop-blur-sm">
                        Mesa {{ table.name }}
                    </p>
                    <p v-if="branch.public_description" class="mt-2 max-w-lg text-sm leading-relaxed text-white/85 lg:text-base">
                        {{ branch.public_description }}
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-white/80">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                            :class="branch.is_open ? 'bg-emerald-500/90 text-white' : 'bg-white/20 text-white'"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="branch.is_open ? 'bg-white animate-pulse' : 'bg-white/60'"
                            />
                            {{ branch.is_open ? t('branch.open_now') : t('branch.closed_now') }}
                        </span>
                        <span v-if="branch.pickup_available" class="rounded-full bg-white/15 px-2.5 py-1 text-xs">Retirada</span>
                        <span v-if="branch.delivery_available" class="rounded-full bg-white/15 px-2.5 py-1 text-xs">
                            Entrega{{ branch.delivery_radius_km ? ` · até ${branch.delivery_radius_km} km` : '' }}
                        </span>
                    </div>
                    <p v-if="branch.full_address" class="mt-3 text-xs text-white/70 lg:text-sm">{{ branch.full_address }}</p>
                    <button
                        v-if="showMapButton"
                        type="button"
                        class="mt-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-white/25"
                        @click="mapOpen = true"
                    >
                        <NavIcon name="map-pin" size="sm" />
                        Ver no mapa
                    </button>
                    <a
                        v-if="branch.instagram?.url"
                        :href="branch.instagram.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-white/25"
                    >
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path
                                d="M12 2.163c3.204 0 3.584.012 4.85.07 1.17.054 1.97.24 2.427.403a4.92 4.92 0 011.7 1.103 4.92 4.92 0 011.103 1.7c.163.457.349 1.257.403 2.427.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.054 1.17-.24 1.97-.403 2.427a4.92 4.92 0 01-1.103 1.7 4.92 4.92 0 01-1.7 1.103c-.457.163-1.257.349-2.427.403-1.266.058-1.646.07-4.85.07s-3.584-.012-4.85-.07c-1.17-.054-1.97-.24-2.427-.403a4.92 4.92 0 01-1.7-1.103 4.92 4.92 0 01-1.103-1.7c-.163-.457-.349-1.257-.403-2.427C2.175 15.747 2.163 15.367 2.163 12s.012-3.584.07-4.85c.054-1.17.24-1.97.403-2.427a4.92 4.92 0 011.103-1.7 4.92 4.92 0 011.7-1.103c.457-.163 1.257-.349 2.427-.403C8.416 2.175 8.796 2.163 12 2.163zm0 1.622c-3.15 0-3.516.012-4.746.07-1.017.046-1.57.215-1.936.357a3.3 3.3 0 00-1.205.786 3.3 3.3 0 00-.786 1.205c-.142.366-.311.919-.357 1.936-.058 1.23-.07 1.596-.07 4.746s.012 3.516.07 4.746c.046 1.017.215 1.57.357 1.936.18.466.42.86.786 1.205.345.345.739.586 1.205.786.366.142.919.311 1.936.357 1.23.058 1.596.07 4.746.07s3.516-.012 4.746-.07c1.017-.046 1.57-.215 1.936-.357a3.3 3.3 0 001.205-.786 3.3 3.3 0 00.786-1.205c.142-.366.311-.919.357-1.936.058-1.23.07-1.596.07-4.746s-.012-3.516-.07-4.746c-.046-1.017-.215-1.57-.357-1.936a3.3 3.3 0 00-.786-1.205 3.3 3.3 0 00-1.205-.786c-.366-.142-.919-.311-1.936-.357-1.23-.058-1.596-.07-4.746-.07zM12 7.378a4.622 4.622 0 100 9.244 4.622 4.622 0 000-9.244zm0 1.622a3 3 0 110 6 3 3 0 010-6zm5.884-3.19a1.08 1.08 0 100 2.16 1.08 1.08 0 000-2.16z"
                            />
                        </svg>
                        {{ branch.instagram.label }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section v-if="banners?.length" class="mx-auto max-w-7xl px-4 pt-4 lg:px-8">
        <div class="scrollbar-hide flex gap-3 overflow-x-auto pb-1">
            <a
                v-for="banner in banners"
                :key="banner.id"
                :href="banner.link_url || '#'"
                class="relative h-28 w-72 shrink-0 overflow-hidden rounded-2xl sm:h-32 sm:w-80"
                :class="{ 'pointer-events-none': !banner.link_url }"
            >
                <img v-if="banner.image_url" :src="banner.image_url" :alt="banner.title || ''" class="h-full w-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
                <p v-if="banner.title" class="absolute bottom-3 left-3 text-sm font-semibold text-white">{{ banner.title }}</p>
            </a>
        </div>
    </section>

    <section v-if="combos?.length" class="mx-auto max-w-7xl px-4 pt-6 lg:px-8">
        <h2 class="mb-3 font-display text-lg font-semibold text-stone-900">Combos</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="combo in combos"
                :key="combo.id"
                class="flex overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm"
            >
                <img v-if="combo.image_url" :src="combo.image_url" :alt="combo.name" class="h-24 w-24 shrink-0 object-cover" />
                <div class="flex min-w-0 flex-1 flex-col p-3">
                    <h3 class="font-semibold text-stone-900">{{ combo.name }}</h3>
                    <p v-if="combo.description" class="line-clamp-2 text-xs text-stone-500">{{ combo.description }}</p>
                    <p class="mt-1 text-xs text-stone-400">
                        {{ combo.items?.map((i) => `${i.quantity}× ${i.product_name}`).join(', ') }}
                    </p>
                    <div class="mt-auto flex items-center justify-between pt-2">
                        <span class="font-semibold" style="color: var(--menu-primary)">{{ formatPrice(parseFloat(combo.price)) }}</span>
                        <button type="button" class="menu-btn-primary !px-3 !py-1.5 text-xs" :disabled="!canAddToCart" @click="addCombo(combo)">
                            Adicionar
                        </button>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section v-if="featured?.length && !searchQuery" class="mx-auto max-w-7xl px-4 pt-6 lg:px-8">
        <h2 class="mb-3 font-display text-lg font-semibold text-stone-900">Destaques</h2>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <MenuProductCard
                v-for="product in featured"
                :key="'f-' + product.id"
                :product="product"
                :can-order="canAddToCart"
                :quantity="getQty(product.id)"
                @add="requestAdd(product)"
                @increment="increment(product)"
                @decrement="decrementProduct(product)"
            />
        </div>
    </section>

    <div
        v-if="!canAddToCart || errors.branch || errors.guest"
        class="mx-auto max-w-7xl px-4 pt-4 lg:px-8"
        role="alert"
    >
        <div class="rounded-2xl border border-amber-200/80 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <template v-if="errors.branch">{{ errors.branch }}</template>
            <template v-else-if="errors.guest">{{ errors.guest }}</template>
            <template v-else>{{ t('branch.orders_closed') }}</template>
        </div>
    </div>

    <!-- Mobile category nav -->
    <nav
        v-if="categories.length"
        class="sticky top-14 z-20 border-b border-stone-200 bg-white/95 backdrop-blur-md lg:hidden"
    >
        <div class="scrollbar-hide flex gap-2 overflow-x-auto px-4 py-3">
            <button
                v-for="cat in categories"
                :key="cat.id"
                type="button"
                class="menu-chip"
                :class="{ 'menu-chip-active': activeCategory === cat.id }"
                @click="scrollToCategory(cat.id)"
            >
                {{ cat.name }}
            </button>
        </div>
    </nav>

    <div class="mx-auto max-w-7xl px-4 py-6 lg:flex lg:items-start lg:gap-8 lg:px-8 lg:py-8">
        <!-- Desktop category sidebar -->
        <aside v-if="categories.length" class="hidden w-52 shrink-0 lg:block">
            <nav class="sticky top-24 space-y-1">
                <p class="mb-3 px-2 text-xs font-semibold uppercase tracking-wider text-stone-400">Categorias</p>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    type="button"
                    class="block w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium transition"
                    :class="
                        activeCategory === cat.id
                            ? 'text-white shadow-sm'
                            : 'text-stone-600 hover:bg-white hover:text-stone-900'
                    "
                    :style="activeCategory === cat.id ? { backgroundColor: 'var(--menu-primary)' } : {}"
                    @click="scrollToCategory(cat.id)"
                >
                    {{ cat.name }}
                </button>
            </nav>
        </aside>

        <!-- Products: busca fixa + lista com rolagem própria (desktop) -->
        <div
            class="min-w-0 flex-1 pb-28 lg:sticky lg:top-20 lg:flex lg:max-h-[calc(100vh-5.5rem)] lg:min-h-0 lg:flex-col lg:pb-0"
        >
            <div
                v-if="categories.length"
                class="mb-4 shrink-0 sticky top-[7.25rem] z-10 bg-[var(--menu-bg)]/95 py-1 backdrop-blur-md lg:static lg:z-auto lg:border-b lg:border-stone-200/80 lg:bg-[var(--menu-bg)] lg:py-0"
            >
                <label class="sr-only" for="menu-search">Buscar no cardápio</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-stone-400">
                        <NavIcon name="search" size="sm" />
                    </span>
                    <input
                        id="menu-search"
                        v-model="searchQuery"
                        type="search"
                        class="menu-input !bg-white !py-2.5 !pl-10"
                        placeholder="Buscar prato ou bebida..."
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-stone-500"
                        @click="searchQuery = ''"
                    >
                        Limpar
                    </button>
                </div>
            </div>

            <div
                ref="catalogScrollRef"
                class="scrollbar-thin lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:overscroll-y-contain lg:pr-1 lg:pt-4"
            >
                <div v-if="!categories.length" class="rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center">
                    <p class="font-display text-lg text-stone-600">Cardápio em breve</p>
                    <p class="mt-2 text-sm text-stone-400">Nenhum produto disponível nesta unidade.</p>
                </div>

                <p
                    v-else-if="searchQuery && !filteredCategories.length"
                    class="rounded-2xl bg-white p-8 text-center text-sm text-stone-500"
                >
                    Nenhum item encontrado para “{{ searchQuery }}”.
                </p>

                <section
                    v-for="cat in filteredCategories"
                    :id="`category-${cat.id}`"
                    :key="cat.id"
                    class="mb-10 scroll-mt-36 lg:scroll-mt-4"
                >
                    <h2 class="mb-4 font-display text-xl font-semibold text-stone-900 lg:text-2xl">{{ cat.name }}</h2>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:gap-4">
                        <div v-for="product in cat.products" :key="product.id" class="min-w-0">
                            <MenuProductCard
                                :product="product"
                                :can-order="canAddToCart"
                                :quantity="getQty(product.id)"
                                @add="requestAdd(product)"
                                @increment="increment(product)"
                                @decrement="decrementProduct(product)"
                            />
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Desktop cart -->
        <aside v-if="canAddToCart" class="hidden w-[22rem] shrink-0 lg:block xl:w-96">
            <div class="sticky top-20 max-h-[calc(100vh-5.5rem)] overflow-y-auto overscroll-y-contain">
            <MenuCart
                ref="desktopCartRef"
                :cart="cart"
                :branch="branch"
                :table="table"
                :can-order="canAddToCart"
                :can-checkout="canCheckout"
                :login-url="loginUrl"
                v-model:guest-name="guestName"
                v-model:guest-phone="guestPhone"
                v-model:guest-email="guestEmail"
                :show-guest-email="guestCheckoutEnabled && !loggedCustomer"
                v-model:order-type="orderType"
                v-model:coupon-code="couponCode"
                v-model:delivery-address="deliveryAddress"
                v-model:order-disposables="orderDisposables"
                v-model:tip-amount="tipAmount"
                v-model:scheduled-for="scheduledFor"
                v-model:payment-method="paymentMethod"
                v-model:payment-channel="paymentChannel"
                :payment-settings="paymentSettings"
                :processing="processing"
                @increment="incrementItem"
                @decrement="decrementItem"
                @checkout="checkout"
            />
            </div>
        </aside>
    </div>

    <!-- Mobile bottom bar -->
    <div
        v-if="canAddToCart && cart.length && !mobileCartOpen"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-stone-200 bg-white/95 p-4 shadow-menu-lg backdrop-blur-md lg:hidden"
    >
        <button
            type="button"
            class="menu-btn-primary flex w-full items-center justify-between gap-3"
            @click="mobileCartOpen = true"
        >
            <span class="flex h-7 min-w-[1.75rem] items-center justify-center rounded-lg bg-white/25 px-2 text-sm font-bold">
                {{ cartItemCount }}
            </span>
            <span class="flex-1 text-left">Ver sacola</span>
            <span class="font-display text-base">{{ formatPrice(cartTotal) }}</span>
        </button>
    </div>

    <!-- Mobile cart drawer -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileCartOpen"
                class="fixed inset-0 z-50 bg-black/50 lg:hidden"
                @click="mobileCartOpen = false"
            />
        </Transition>
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full"
        >
            <div
                v-if="mobileCartOpen"
                class="fixed inset-x-0 bottom-0 z-50 flex max-h-[92dvh] flex-col overflow-hidden rounded-t-3xl bg-white shadow-menu-lg lg:hidden"
            >
                <div class="flex shrink-0 justify-center py-2">
                    <span class="h-1 w-10 rounded-full bg-stone-300" />
                </div>
                <MenuCart
                    ref="mobileCartRef"
                    compact
                    :cart="cart"
                    :branch="branch"
                    :table="table"
                    :can-order="canAddToCart"
                    :can-checkout="canCheckout"
                    :login-url="loginUrl"
                    v-model:guest-name="guestName"
                    v-model:guest-phone="guestPhone"
                    v-model:guest-email="guestEmail"
                    :show-guest-email="guestCheckoutEnabled && !loggedCustomer"
                    v-model:order-type="orderType"
                    v-model:coupon-code="couponCode"
                    v-model:delivery-address="deliveryAddress"
                    v-model:order-disposables="orderDisposables"
                    v-model:tip-amount="tipAmount"
                    v-model:scheduled-for="scheduledFor"
                    v-model:payment-method="paymentMethod"
                    v-model:payment-channel="paymentChannel"
                    :payment-settings="paymentSettings"
                    :processing="processing"
                    :products-by-id="catalogProductsById"
                    @increment="incrementItem"
                    @decrement="decrementItem"
                    @checkout="checkout"
                    @close="mobileCartOpen = false"
                />
            </div>
        </Transition>
    </Teleport>

    <MenuProductModal
        :product="modalProduct"
        :open="modalOpen"
        :theme-color="page.props.tenant?.theme_primary_color"
        @close="modalOpen = false"
        @confirm="onModalConfirm"
    />

    <BranchMapModal
        v-if="googleMaps?.api_key"
        :open="mapOpen"
        :branch="branch"
        :api-key="googleMaps.api_key"
        @close="mapOpen = false"
    />

    <ChatWidget
        :enabled="chatEnabled"
        :tenant-slug="tenantSlug"
        :branch-slug="branch.slug"
        :initial-guest-name="chatGuestProfile?.name ?? ''"
        :initial-guest-phone="chatGuestProfile?.phone ?? ''"
    />
</template>
