<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: PlatformLayout });

defineProps({
    stats: Object,
    recentTenants: Array,
});
</script>

<template>
    <Head title="Dashboard" />

    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard da Plataforma</h1>

    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="platform-card">
            <p class="platform-stat-label">Total restaurantes</p>
            <p class="text-2xl font-bold">{{ stats.tenants_total }}</p>
        </div>
        <div class="platform-card">
            <p class="platform-stat-label">Ativos</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.tenants_active }}</p>
        </div>
        <div class="platform-card">
            <p class="platform-stat-label">Suspensos</p>
            <p class="text-2xl font-bold text-red-600">{{ stats.tenants_suspended }}</p>
        </div>
        <div class="platform-card">
            <p class="platform-stat-label">Assinaturas em atraso</p>
            <p class="text-2xl font-bold text-amber-600">{{ stats.subscriptions_overdue }}</p>
        </div>
    </div>

    <div class="mt-8 platform-card">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Restaurantes recentes</h2>
            <Link :href="route('platform.tenants.index')" class="text-sm text-indigo-600 hover:underline">
                Ver todos
            </Link>
        </div>
        <ul class="divide-y divide-slate-100">
            <li v-for="t in recentTenants" :key="t.id" class="flex items-center justify-between py-3">
                <div>
                    <p class="font-medium">{{ t.name }}</p>
                    <p class="text-sm text-slate-500">/{{ t.slug }}</p>
                </div>
                <span
                    class="rounded-full px-2 py-0.5 text-xs"
                    :class="t.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                >
                    {{ t.status === 'active' ? 'Ativo' : 'Suspenso' }}
                </span>
            </li>
        </ul>
    </div>
</template>
