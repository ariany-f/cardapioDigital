<script setup>
import CategoriesManager from '@/Components/Catalog/CategoriesManager.vue';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

defineOptions({ layout: PlatformLayout });

defineProps({
    platformTenant: Object,
    categories: Array,
    filters: Object,
});
</script>

<template>
    <CategoriesManager mode="platform" :platform-tenant="platformTenant" :categories="categories" :filters="filters" />
</template>
