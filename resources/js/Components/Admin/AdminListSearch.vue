<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    href: { type: String, required: true },
    filters: { type: Object, default: () => ({}) },
    placeholder: { type: String, default: 'Buscar...' },
    wrapperClass: { type: String, default: 'mt-6' },
});

const apply = (value) => {
    const q = value?.trim() || undefined;
    router.get(props.href, { ...props.filters, q }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <div :class="['flex flex-wrap gap-3', wrapperClass]">
        <input
            type="search"
            class="admin-input max-w-sm"
            :placeholder="placeholder"
            :value="filters?.q ?? ''"
            @change="apply($event.target.value)"
        />
    </div>
</template>
