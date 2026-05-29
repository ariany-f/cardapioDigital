<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'customer',
        validator: (v) => ['customer', 'restaurant'].includes(v),
    },
    compact: { type: Boolean, default: false },
    showAlerts: { type: Boolean, default: true },
});

const page = usePage();
const copy = computed(() => page.props.communication_disclaimer?.[props.variant] ?? {});
</script>

<template>
    <aside
        v-if="copy.title"
        class="rounded-2xl border border-stone-200 bg-stone-50 text-sm text-stone-700"
        :class="compact ? 'px-3 py-2.5' : 'px-4 py-3'"
        role="note"
    >
        <p class="font-semibold text-stone-900">{{ copy.title }}</p>
        <p class="mt-1 leading-relaxed">{{ copy.body }}</p>
        <p v-if="showAlerts && copy.alerts" class="mt-2 text-xs leading-relaxed text-stone-600">
            {{ copy.alerts }}
        </p>
    </aside>
</template>
