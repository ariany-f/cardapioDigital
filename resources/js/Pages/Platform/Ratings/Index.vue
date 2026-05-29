<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    ratings: Object,
    filters: Object,
    tenants: Array,
});

const apply = (patch) =>
    router.get(route('platform.ratings.index'), { ...props.filters, ...patch }, { preserveState: true });

const setStatus = (rating, status) => {
    if (!confirm(status === 'hidden' ? 'Ocultar esta avaliação?' : 'Publicar novamente?')) return;
    router.patch(route('platform.ratings.update-status', rating.id), { status });
};

const stars = (n) => (n ? '★'.repeat(n) : '—');
const formatDate = (iso) => (iso ? new Date(iso).toLocaleString('pt-BR') : '');
</script>

<template>
    <Head title="Avaliações" />

    <h1 class="text-2xl font-bold text-slate-900">Avaliações</h1>
    <p class="mt-1 text-sm text-stone-500">
        Moderação global: restaurante, pedido e entrega de todos os tenants.
    </p>

    <div class="mt-6 flex flex-wrap gap-2">
        <select class="admin-input w-auto" :value="filters?.tenant_id ?? ''" @change="apply({ tenant_id: $event.target.value || undefined })">
            <option value="">Todos os restaurantes</option>
            <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
        <select class="admin-input w-auto" :value="filters?.status ?? ''" @change="apply({ status: $event.target.value || undefined })">
            <option value="">Todos os status</option>
            <option value="approved">Publicadas</option>
            <option value="hidden">Ocultas</option>
        </select>
        <select class="admin-input w-auto" :value="filters?.type ?? ''" @change="apply({ type: $event.target.value || undefined })">
            <option value="">Todos os tipos</option>
            <option value="restaurant">Restaurante</option>
            <option value="order">Pedido</option>
            <option value="delivery">Entrega</option>
        </select>
    </div>

    <div class="admin-table-wrap mt-4">
        <table class="admin-table text-sm">
            <thead>
                <tr>
                    <th>Restaurante</th>
                    <th>Pedido</th>
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
                        <p class="font-medium">{{ r.tenant?.name }}</p>
                        <p class="text-xs text-stone-500">{{ r.branch?.name }}</p>
                    </td>
                    <td>
                        <Link
                            :href="`/${r.tenant?.slug}/admin/orders/${r.order?.id}`"
                            class="font-medium text-indigo-600 hover:underline"
                        >
                            {{ r.order?.order_number }}
                        </Link>
                    </td>
                    <td>
                        <span class="text-amber-600">{{ stars(r.order_rating) }}</span>
                        <p v-if="r.order_comment" class="max-w-[10rem] truncate text-xs text-stone-500">{{ r.order_comment }}</p>
                    </td>
                    <td>
                        <span v-if="r.delivery_rating" class="text-amber-600">{{ stars(r.delivery_rating) }}</span>
                        <span v-else>—</span>
                    </td>
                    <td>
                        <span class="text-amber-600">{{ stars(r.restaurant_rating) }}</span>
                        <p v-if="r.restaurant_comment" class="max-w-[10rem] truncate text-xs text-stone-500">{{ r.restaurant_comment }}</p>
                    </td>
                    <td>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="r.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-600'"
                        >
                            {{ r.status === 'approved' ? 'Publicada' : 'Oculta' }}
                        </span>
                        <p class="text-[10px] text-stone-400">{{ formatDate(r.created_at) }}</p>
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
</template>
