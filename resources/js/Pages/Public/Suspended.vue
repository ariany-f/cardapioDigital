<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: PublicLayout });

defineProps({
    tenant: Object,
    reason: String,
});
</script>

<template>
    <Head title="Indisponível" />

    <div class="rounded-xl bg-white p-8 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-2xl">
            ⏸
        </div>
        <h1 class="text-xl font-bold text-gray-900">{{ tenant?.name }}</h1>
        <p class="mt-3 text-gray-600">
            Este cardápio está temporariamente indisponível.
        </p>
        <p v-if="reason === 'subscription'" class="mt-2 text-sm text-amber-700">
            Entre em contato com o restaurante para mais informações.
        </p>
    </div>
</template>
