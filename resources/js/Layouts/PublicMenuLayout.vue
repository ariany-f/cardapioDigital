<script setup>
import LegalFooter from '@/Components/Public/LegalFooter.vue';
import { useSeoAnalytics } from '@/composables/useSeoAnalytics';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

useSeoAnalytics();

const page = usePage();
const tenant = computed(() => page.props.tenant);
const customer = computed(() => page.props.auth?.customer);
const t = (key) => page.props.translations?.[key] ?? key;

const brandColor = computed(() => tenant.value?.theme_primary_color || '#f4003a');
</script>

<template>
    <div
        class="public-menu min-h-screen bg-[var(--menu-bg)] font-menu text-[var(--menu-text)]"
        :style="{
            '--primary': brandColor,
            '--menu-primary': brandColor,
        }"
    >
        <header class="sticky top-0 z-30 border-b border-gray-200/90 bg-white shadow-sm">
            <div class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-3 px-4 lg:h-16 lg:px-8">
                <Link
                    v-if="tenant"
                    :href="route('tenant.home', { tenant: tenant.slug })"
                    class="flex min-w-0 items-center gap-2.5"
                >
                    <span
                        v-if="tenant.logo_path"
                        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border border-gray-100 bg-gray-50"
                    >
                        <img :src="`/storage/${tenant.logo_path}`" :alt="tenant.name" class="h-full w-full object-cover" />
                    </span>
                    <span
                        v-else
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white shadow-brand"
                        :style="{ backgroundColor: 'var(--menu-primary)' }"
                    >
                        {{ tenant.name?.charAt(0) }}
                    </span>
                    <span class="truncate text-base font-bold text-gray-900 lg:text-lg">{{ tenant.name }}</span>
                </Link>
                <nav class="flex shrink-0 items-center gap-1 text-sm">
                    <Link
                        v-if="tenant?.guest_checkout_enabled"
                        :href="route('tenant.track.lookup', { tenant: tenant.slug })"
                        class="rounded-full px-3 py-2 font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                    >
                        {{ t('nav.track') }}
                    </Link>
                    <Link
                        v-if="tenant"
                        :href="route('tenant.support', { tenant: tenant.slug })"
                        class="rounded-full px-3 py-2 font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
                    >
                        Falar com a loja
                    </Link>
                    <Link
                        v-if="tenant && customer"
                        :href="route('tenant.conta.dashboard', { tenant: tenant.slug })"
                        class="rounded-full px-3 py-2 font-semibold text-gray-800 transition hover:bg-[var(--menu-primary-soft)]"
                        :style="{ color: 'var(--menu-primary)' }"
                    >
                        {{ customer.name.split(' ')[0] }}
                    </Link>
                    <Link
                        v-else-if="tenant"
                        :href="route('tenant.conta.login', { tenant: tenant.slug })"
                        class="menu-btn-primary !px-4 !py-2 text-xs lg:text-sm"
                    >
                        {{ t('nav.account') }}
                    </Link>
                </nav>
            </div>
        </header>

        <slot />

        <footer v-if="tenant" class="border-t border-stone-200 bg-white px-4 py-4">
            <LegalFooter :tenant-slug="tenant.slug" />
        </footer>
    </div>
</template>
