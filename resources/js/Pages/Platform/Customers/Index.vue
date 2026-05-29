<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PlatformLayout });

const props = defineProps({ customers: Object, filters: Object });

const apply = (q) => router.get(route('platform.customers.index'), { q: q || undefined }, { preserveState: true });
</script>

<template>
    <Head title="Clientes — Plataforma" />
    <h1 class="text-2xl font-bold text-slate-900">Clientes (conta global)</h1>
    <p class="mt-1 text-sm text-slate-500">
        Contas que podem pedir em vários restaurantes da plataforma. Suporte sobre pedidos específicos é do restaurante onde comprou.
    </p>

    <input
        type="search"
        class="admin-input mt-4 max-w-md"
        placeholder="Nome, e-mail ou telefone"
        :value="filters?.q ?? ''"
        @change="apply($event.target.value)"
    />

    <div class="admin-table-wrap mt-6">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Pedidos</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="c in customers.data" :key="c.id">
                    <td>
                        <Link :href="route('platform.customers.show', c.id)" class="text-indigo-600 hover:underline">
                            {{ c.name }}
                        </Link>
                    </td>
                    <td>{{ c.email }}</td>
                    <td>{{ c.phone }}</td>
                    <td>{{ c.orders_count }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="customers.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link
            v-for="link in customers.links"
            :key="link.label"
            :href="link.url || '#'"
            class="rounded px-3 py-1 text-sm"
            :class="link.active ? 'bg-indigo-600 text-white' : 'bg-stone-100'"
            v-html="link.label"
        />
    </div>
</template>
