<script setup>
import ProductsManager from '@/Components/Catalog/ProductsManager.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    products: Object,
    categories: Array,
    branches: Array,
    editingProduct: Object,
    creating: Boolean,
    filters: Object,
});
</script>

<template>
    <ProductsManager
        mode="tenant"
        :products="products"
        :categories="categories"
        :branches="branches"
        :editing-product="editingProduct"
        :creating="creating"
        :filters="filters"
    />
</template>
