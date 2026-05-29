<script setup>
import ProductsManager from '@/Components/Catalog/ProductsManager.vue';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

defineOptions({ layout: PlatformLayout });

defineProps({
    platformTenant: Object,
    products: Object,
    categories: Array,
    branches: Array,
    editingProduct: Object,
    creating: Boolean,
});
</script>

<template>
    <ProductsManager
        mode="platform"
        :platform-tenant="platformTenant"
        :products="products"
        :categories="categories"
        :branches="branches"
        :editing-product="editingProduct"
        :creating="creating"
    />
</template>
