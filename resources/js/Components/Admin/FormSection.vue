<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    variant: { type: String, default: 'default' },
    columns: { type: Number, default: 2 },
});
</script>

<template>
    <section
        class="rounded-xl border p-4 sm:p-5"
        :class="
            variant === 'highlight'
                ? 'border-amber-200 bg-amber-50/80'
                : 'border-stone-200 bg-stone-50/50'
        "
    >
        <header class="mb-4 border-b pb-3" :class="variant === 'highlight' ? 'border-amber-200/80' : 'border-stone-200'">
            <h3 class="text-sm font-semibold" :class="variant === 'highlight' ? 'text-amber-950' : 'text-stone-900'">
                {{ title }}
            </h3>
            <p v-if="description" class="mt-1 text-xs leading-relaxed text-stone-600">
                {{ description }}
            </p>
        </header>
        <div
            class="grid gap-3"
            :class="columns === 1 ? 'grid-cols-1' : 'sm:grid-cols-2'"
        >
            <slot />
        </div>
    </section>
</template>
