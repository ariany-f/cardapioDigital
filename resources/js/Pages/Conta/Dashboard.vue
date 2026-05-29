<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    customer: Object,
    orders: Array,
    globalAccount: { type: Boolean, default: false },
});

const page = usePage();
const tenant = computed(() => page.props.tenant);
const t = (key) => page.props.translations?.[key] ?? key;

const statusLabel = (status) => t(`order.status.${status}`) || status;

const logoutRoute = computed(() =>
    props.globalAccount
        ? route('app.conta.logout')
        : route('tenant.conta.logout', { tenant: tenant.value.slug }),
);

const logout = () => router.post(logoutRoute.value);

const formatMoney = (value) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value));

const trackHref = (order) =>
    route('tenant.track', {
        tenant: order.tenant?.slug ?? tenant.value?.slug,
        order_number: order.order_number,
    });

const repeatHref = (order) =>
    route('tenant.conta.orders.repeat', {
        tenant: order.tenant?.slug ?? tenant.value?.slug,
        order: order.id,
    });
</script>

<template>
    <Head title="Minha conta" />

    <div class="mx-auto max-w-2xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-stone-900">Olá, {{ customer.name }}</h1>
                <p class="text-sm text-stone-600">{{ customer.email }} · {{ customer.phone }}</p>
            </div>
            <button
                type="button"
                class="rounded-xl border border-stone-200 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50"
                @click="logout"
            >
                Sair
            </button>
        </div>

        <p v-if="globalAccount" class="mt-4 text-sm text-stone-600">
            Pedidos em todos os restaurantes em que você comprou.
        </p>

        <section class="mt-8">
            <h2 class="mb-4 font-semibold text-stone-900">Seus pedidos</h2>

            <div
                v-if="!orders.length"
                class="rounded-2xl border border-dashed border-stone-200 bg-white p-8 text-center text-stone-500"
            >
                Você ainda não fez pedidos.
                <p v-if="globalAccount" class="mt-2 text-sm">
                    Acesse o cardápio de um restaurante para fazer seu primeiro pedido.
                </p>
                <Link
                    v-else-if="tenant"
                    :href="route('tenant.home', { tenant: tenant.slug })"
                    class="mt-2 block font-semibold text-orange-600"
                >
                    Ver unidades
                </Link>
            </div>

            <ul v-else class="space-y-3">
                <li
                    v-for="order in orders"
                    :key="order.id"
                    class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm"
                >
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold text-stone-900">#{{ order.order_number }}</p>
                            <p class="text-sm text-stone-500">
                                <span v-if="order.tenant?.name" class="font-medium text-stone-700">
                                    {{ order.tenant.name }}
                                </span>
                                <span v-if="order.tenant?.name && order.branch?.name"> · </span>
                                {{ order.branch?.name }}
                                · {{ new Date(order.created_at).toLocaleString('pt-BR') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-stone-900">{{ formatMoney(order.total) }}</p>
                            <span class="mt-1 inline-block rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-700">
                                {{ statusLabel(order.status) }}
                            </span>
                        </div>
                    </div>
                    <div
                        v-if="order.show_delivery_code && order.delivery_confirmation_code"
                        class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Código de entrega</p>
                        <p class="mt-1 font-mono text-2xl font-bold tracking-widest text-amber-950">
                            {{ order.delivery_confirmation_code }}
                        </p>
                        <p class="mt-1 text-xs text-amber-900/80">
                            Mostre ao entregador para confirmar o recebimento.
                        </p>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-3 text-sm">
                        <Link :href="trackHref(order)" class="font-semibold text-orange-600 hover:text-orange-700">
                            Acompanhar
                        </Link>
                        <Link
                            v-if="order.branch?.slug && order.tenant?.slug"
                            :href="repeatHref(order)"
                            class="font-semibold text-stone-600 hover:text-orange-600"
                        >
                            Pedir novamente
                        </Link>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>
