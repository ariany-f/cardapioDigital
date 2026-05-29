<script setup>
defineProps({
    product: { type: Object, required: true },
    canOrder: { type: Boolean, default: false },
    quantity: { type: Number, default: 0 },
});

const emit = defineEmits(['add', 'increment', 'decrement']);

const formatPrice = (value) =>
    parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const initials = (name) =>
    name
        .split(' ')
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
</script>

<template>
    <article
        class="flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-stone-200/80 bg-white shadow-sm transition hover:border-stone-300 hover:shadow-menu"
        :class="{ 'opacity-70': !canOrder || product.out_of_stock }"
    >
        <div class="flex gap-3 p-3 lg:gap-4 lg:p-4">
            <div class="relative h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-stone-100 sm:h-24 sm:w-24">
                <img
                    v-if="product.image_url"
                    :src="product.image_url"
                    :alt="product.name"
                    class="h-full w-full object-cover"
                    loading="lazy"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center bg-gradient-to-br from-stone-100 to-stone-200 font-display text-base font-semibold text-stone-500"
                >
                    {{ initials(product.name) }}
                </div>
                <span
                    v-if="product.is_featured"
                    class="absolute left-1 top-1 rounded-md bg-amber-400 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-950"
                >
                    Destaque
                </span>
            </div>

            <div class="min-w-0 flex-1">
                <h3 class="font-semibold leading-snug text-stone-900">{{ product.name }}</h3>
                <p
                    v-if="product.description"
                    class="mt-1 line-clamp-2 text-xs leading-relaxed text-stone-500 sm:text-sm"
                >
                    {{ product.description }}
                </p>
            </div>
        </div>

        <div
            class="mt-auto flex items-center justify-between gap-2 border-t border-stone-100 px-3 py-2.5 lg:px-4"
        >
            <p class="font-display text-base font-semibold" style="color: var(--menu-primary)">
                {{ formatPrice(product.base_price) }}
            </p>

            <div v-if="quantity > 0" class="flex shrink-0 items-center gap-1.5">
                <button type="button" class="menu-qty-btn" :disabled="!canOrder" @click="emit('decrement')">−</button>
                <span class="min-w-[1.25rem] text-center text-sm font-semibold">{{ quantity }}</span>
                <button type="button" class="menu-qty-btn" :disabled="!canOrder" @click="emit('increment')">+</button>
            </div>
            <span v-else-if="product.out_of_stock" class="text-xs font-medium text-stone-500">Indisponível</span>
            <button
                v-else
                type="button"
                class="menu-btn-primary shrink-0 !px-4 !py-2 text-xs sm:text-sm"
                :disabled="!canOrder"
                @click="emit('add')"
            >
                {{ product.has_customization || product.has_variations ? 'Personalizar' : 'Adicionar' }}
            </button>
        </div>
    </article>
</template>
