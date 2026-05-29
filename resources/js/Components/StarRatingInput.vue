<script setup>
const model = defineModel({ type: Number, default: 5 });

defineProps({
    label: { type: String, required: true },
});
</script>

<template>
    <div>
        <p class="text-sm font-medium text-stone-800">{{ label }}</p>
        <div class="mt-1 flex gap-1">
            <button
                v-for="n in 5"
                :key="n"
                type="button"
                class="text-2xl transition hover:scale-110"
                :class="n <= model ? 'text-amber-500' : 'text-stone-300'"
                :aria-label="`${n} estrela${n > 1 ? 's' : ''}`"
                @click="model = n"
            >
                ★
            </button>
        </div>
    </div>
</template>
