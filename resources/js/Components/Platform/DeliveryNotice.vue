<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'onboarding',
        validator: (v) => ['onboarding', 'motoboys_module', 'motoboy_admin'].includes(v),
    },
});

const page = usePage();
const delivery = computed(() => page.props.communication_disclaimer?.delivery ?? {});

const title = computed(() => {
    if (props.variant === 'motoboy_admin') return delivery.value.motoboy_admin_title;
    if (props.variant === 'motoboys_module') return delivery.value.motoboys_module_label;
    return delivery.value.onboarding_title;
});

const body = computed(() => {
    if (props.variant === 'motoboy_admin') return delivery.value.motoboy_admin_body;
    if (props.variant === 'motoboys_module') return delivery.value.motoboys_module_help;
    return delivery.value.onboarding_body;
});
</script>

<template>
    <aside
        v-if="title"
        class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950"
        role="note"
    >
        <p class="font-semibold">{{ title }}</p>
        <p class="mt-1 leading-relaxed text-sky-900/90">{{ body }}</p>
        <p v-if="variant === 'onboarding' && delivery.onboarding_plan_note" class="mt-2 text-xs text-sky-800/90">
            {{ delivery.onboarding_plan_note }}
        </p>
        <slot />
    </aside>
</template>
