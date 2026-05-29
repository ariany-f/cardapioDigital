<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    ratings: Object,
    filters: Object,
    summary: Object,
    motoboyStats: Array,
    canModerateRestaurant: Boolean,
});

const page = usePage();
const tenant = page.props.tenant;

const apply = (patch) =>
    router.get(route('tenant.admin.ratings.index', { tenant: tenant.slug }), { ...props.filters, ...patch }, { preserveState: true });

const setStatus = (rating, status) => {
    if (!confirm(status === 'hidden' ? 'Ocultar esta avaliação?' : 'Publicar novamente?')) return;
    router.patch(route('tenant.admin.ratings.update-status', { tenant: tenant.slug, rating: rating.id }), { status });
};

const stars = (n) => (n ? '★'.repeat(n) : '—');
const formatDate = (iso) => (iso ? new Date(iso).toLocaleString('pt-BR') : '');
</script>

<template>
    <Head title="Avaliações" />

    <h1 class="admin-page-title">Avaliações</h1>
    <p class="mt-1 text-sm text-stone-500">
        Pedido, entrega e restaurante enviados pelos clientes após a entrega. Você pode ocultar avaliações inadequadas.
    </p>

    <div v-if="summary?.count" class="mt-6 grid gap-4 sm:grid-cols-3">
        <div class="admin-card text-center">
            <p class="text-xs font-semibold uppercase text-stone-500">Restaurante</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ summary.restaurant ?? '—' }}</p>
            <p class="text-xs text-stone-400">{{ summary.count }} avaliações</p>
        </div>
        <div class="admin-card text-center">
            <p class="text-xs font-semibold uppercase text-stone-500">Pedido</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ summary.order ?? '—' }}</p>
        </div>
        <div class="admin-card text-center">
            <p class="text-xs font-semibold uppercase text-stone-500">Entrega</p>
            <p class="mt-1 text-2xl font-bold text-amber-600">{{ summary.delivery ?? '—' }}</p>
        </div>
    </div>

    <div v-if="motoboyStats?.length" class="admin-card mt-6">
        <h2 class="text-sm font-semibold text-stone-800">Média por entregador</h2>
        <ul class="mt-3 divide-y divide-stone-100 text-sm">
            <li v-for="m in motoboyStats" :key="m.motoboy_id" class="flex justify-between py-2">
                <span>{{ m.name }}</span>
                <span class="font-medium text-amber-600">{{ m.average }} ★ <span class="text-stone-400">({{ m.count }})</span></span>
            </li>
        </ul>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        <select class="admin-input w-auto" :value="filters?.status ?? ''" @change="apply({ status: $event.target.value || undefined })">
            <option value="">Todos os status</option>
            <option value="approved">Publicadas</option>
            <option value="hidden">Ocultas</option>
        </select>
        <select class="admin-input w-auto" :value="filters?.type ?? ''" @change="apply({ type: $event.target.value || undefined })">
            <option value="">Todos os tipos</option>
            <option value="order">Pedido</option>
            <option value="delivery">Entrega</option>
        </select>
    </div>

    <div class="admin-table-wrap mt-4">
        <table class="admin-table text-sm">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Pedido ★</th>
                    <th>Entrega ★</th>
                    <th>Restaurante ★</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="r in ratings.data" :key="r.id">
                    <td>
                        <Link
                            :href="route('tenant.admin.orders.show', { tenant: tenant.slug, order: r.order?.id })"
                            class="font-medium text-orange-600 hover:underline"
                        >
                            {{ r.order?.order_number }}
                        </Link>
                        <p class="text-xs text-stone-500">{{ r.branch?.name }}</p>
                    </td>
                    <td>{{ r.customer?.name || r.order?.guest_name || '—' }}</td>
                    <td>
                        <span class="text-amber-600">{{ stars(r.order_rating) }}</span>
                        <p v-if="r.order_comment" class="mt-0.5 max-w-[12rem] truncate text-xs text-stone-500">{{ r.order_comment }}</p>
                    </td>
                    <td>
                        <span v-if="r.delivery_rating" class="text-amber-600">{{ stars(r.delivery_rating) }}</span>
                        <span v-else class="text-stone-400">—</span>
                        <p v-if="r.motoboy" class="text-xs text-stone-500">{{ r.motoboy.name }}</p>
                    </td>
                    <td>
                        <span class="text-amber-600">{{ stars(r.restaurant_rating) }}</span>
                        <p v-if="r.restaurant_comment" class="mt-0.5 max-w-[12rem] truncate text-xs text-stone-500">{{ r.restaurant_comment }}</p>
                    </td>
                    <td>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="r.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-600'"
                        >
                            {{ r.status === 'approved' ? 'Publicada' : 'Oculta' }}
                        </span>
                        <p class="mt-1 text-[10px] text-stone-400">{{ formatDate(r.created_at) }}</p>
                    </td>
                    <td class="text-right">
                        <button
                            v-if="r.status === 'approved'"
                            type="button"
                            class="text-xs text-red-600 hover:underline"
                            @click="setStatus(r, 'hidden')"
                        >
                            Ocultar
                        </button>
                        <button v-else type="button" class="text-xs text-emerald-700 hover:underline" @click="setStatus(r, 'approved')">
                            Publicar
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="!ratings.data?.length" class="mt-8 text-center text-sm text-stone-500">Nenhuma avaliação encontrada.</div>
</template>
