<script setup>
import CategoriesManager from '@/Components/Catalog/CategoriesManager.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    categories: Array,
    filters: Object,
});
</script>

<template>
    <CategoriesManager mode="tenant" :categories="categories" :filters="filters" />
</template>
