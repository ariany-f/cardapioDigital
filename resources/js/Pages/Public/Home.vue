<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    tenantName: String,
    tenantDescription: String,
    branches: { type: Array, default: () => [] },
    ratingSummary: { type: Object, default: null },
    seo: Object,
});

const page = usePage();
const tenant = computed(() => page.props.tenant);
const t = (key) => page.props.translations?.[key] ?? key;
</script>

<template>
    <SeoHead :seo="seo" />

    <section class="rounded-3xl border border-gray-200 bg-white p-8 text-center shadow-sm lg:p-12">
        <div
            class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl text-3xl font-bold text-white shadow-brand"
            :style="{ backgroundColor: 'var(--menu-primary)' }"
        >
            {{ (tenant?.name || tenantName)?.charAt(0) }}
        </div>
        <h1 class="text-2xl font-bold text-gray-900 lg:text-3xl">{{ tenant?.name || tenantName }}</h1>
        <p v-if="tenant?.public_description || tenantDescription" class="mx-auto mt-3 max-w-md text-gray-600">
            {{ tenant?.public_description || tenantDescription }}
        </p>
        <div
            v-if="ratingSummary?.count"
            class="mx-auto mt-5 flex flex-wrap justify-center gap-3 text-sm"
        >
            <span
                v-if="ratingSummary.restaurant"
                class="rounded-full bg-amber-50 px-3 py-1 font-medium text-amber-800"
            >
                ★ {{ ratingSummary.restaurant }} restaurante
            </span>
            <span v-if="ratingSummary.order" class="rounded-full bg-gray-100 px-3 py-1 text-gray-700">
                ★ {{ ratingSummary.order }} pedidos
            </span>
            <span v-if="ratingSummary.delivery" class="rounded-full bg-gray-100 px-3 py-1 text-gray-700">
                ★ {{ ratingSummary.delivery }} entrega
            </span>
            <span class="text-gray-400">({{ ratingSummary.count }} avaliações)</span>
        </div>
    </section>

    <section v-if="branches.length > 1" class="mt-8">
        <h2 class="mb-1 text-lg font-bold text-gray-900">{{ t('public.choose_branch_title') }}</h2>
        <p class="mb-4 text-sm text-gray-600">{{ t('public.choose_branch_hint') }}</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <Link
                v-for="branch in branches"
                :key="branch.id"
                :href="branch.url"
                class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:border-[var(--menu-primary)] hover:shadow-md"
            >
                <div v-if="branch.cover_url" class="aspect-[2/1] overflow-hidden bg-gray-100">
                    <img
                        :src="branch.cover_url"
                        :alt="branch.name"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                    />
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-gray-900">{{ branch.name }}</h3>
                            <p v-if="branch.city" class="text-sm text-gray-500">{{ branch.city }}</p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :class="branch.is_open ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'"
                        >
                            {{ branch.is_open ? 'Aberto' : 'Fechado' }}
                        </span>
                    </div>
                    <p v-if="branch.public_description" class="mt-2 line-clamp-2 text-sm text-gray-600">
                        {{ branch.public_description }}
                    </p>
                    <p
                        class="mt-3 text-sm font-semibold group-hover:underline"
                        :style="{ color: 'var(--menu-primary)' }"
                    >
                        Ver cardápio →
                    </p>
                </div>
            </Link>
        </div>
    </section>

    <section v-else class="mt-8 rounded-2xl bg-gray-50 px-5 py-4 text-center text-sm text-gray-500">
        {{ t('public.use_branch_link') }}
    </section>

    <section v-if="tenant" class="mt-8 flex flex-wrap items-center justify-center gap-4 text-sm">
        <Link
            :href="route('tenant.support', { tenant: tenant.slug })"
            class="font-semibold text-gray-600 hover:text-gray-900"
        >
            Falar com o restaurante
        </Link>
        <span class="text-gray-300">|</span>
        <Link
            :href="route('tenant.conta.login', { tenant: tenant.slug })"
            class="font-semibold hover:underline"
            :style="{ color: 'var(--menu-primary)' }"
        >
            Entrar na minha conta
        </Link>
        <span class="text-gray-300">|</span>
        <Link
            :href="route('tenant.conta.register', { tenant: tenant.slug })"
            class="font-semibold text-gray-600 hover:text-gray-900"
        >
            Criar conta
        </Link>
    </section>
</template>
