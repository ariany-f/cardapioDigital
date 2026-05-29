<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: PlatformLayout });

defineProps({ customer: Object, orders: Array });
</script>

<template>
    <Head :title="`Cliente ${customer.name}`" />
    <Link :href="route('platform.customers.index')" class="text-sm text-indigo-600 hover:underline">← Clientes</Link>
    <h1 class="mt-2 text-2xl font-bold">{{ customer.name }}</h1>
    <p class="text-stone-600">{{ customer.email }} · {{ customer.phone }}</p>

    <h2 class="admin-page-title mt-8">Pedidos</h2>
    <ul class="mt-4 space-y-2">
        <li v-for="o in orders" :key="o.id" class="admin-card text-sm">
            <Link :href="`/${o.tenant?.slug}/admin/orders/${o.id}`" class="font-medium text-indigo-600">
                {{ o.order_number }}
            </Link>
            — {{ o.tenant?.name }} · R$ {{ parseFloat(o.total).toFixed(2) }} · {{ o.status }}
        </li>
    </ul>
</template>
