<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tenantSlug: { type: String, default: null },
    centered: { type: Boolean, default: true },
    variant: {
        type: String,
        default: 'public',
        validator: (v) => ['public', 'admin', 'platform'].includes(v),
    },
});

const page = usePage();

const termsHref = computed(() =>
    props.tenantSlug
        ? route('tenant.legal.terms', { tenant: props.tenantSlug })
        : route('legal.terms'),
);

const disclaimer = computed(() => page.props.communication_disclaimer);

const customerHint = computed(() =>
    props.variant === 'public' ? disclaimer.value?.footer_customer_hint ?? '' : '',
);

const summary = computed(() => {
    if (props.variant === 'platform') {
        return disclaimer.value?.footer_platform ?? disclaimer.value?.footer_short ?? '';
    }

    if (props.variant === 'admin') {
        return disclaimer.value?.footer_admin ?? disclaimer.value?.footer_short ?? '';
    }

    return disclaimer.value?.footer_short ?? '';
});

const textClass = computed(() => {
    if (props.variant === 'admin' || props.variant === 'platform') {
        return 'text-[11px] leading-relaxed text-gray-500';
    }

    return 'text-[11px] leading-relaxed text-stone-500';
});

const linkClass = computed(() => {
    if (props.variant === 'admin' || props.variant === 'platform') {
        return 'text-gray-600 hover:text-gray-800';
    }

    return 'text-stone-600 hover:text-stone-800';
});
</script>

<template>
    <div :class="[centered ? 'text-center' : '', textClass]">
        <p v-if="customerHint" class="mx-auto max-w-lg text-stone-400">{{ customerHint }}</p>
        <p v-if="summary" class="mx-auto max-w-lg" :class="customerHint ? 'mt-2' : ''">{{ summary }}</p>
        <p class="mt-2">
            <Link :href="termsHref" class="font-medium underline-offset-2 hover:underline" :class="linkClass">
                Termos de uso
            </Link>
        </p>
    </div>
</template>
