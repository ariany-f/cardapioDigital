<script setup>
import LegalFooter from '@/Components/Public/LegalFooter.vue';
import { useSeoAnalytics } from '@/composables/useSeoAnalytics';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

useSeoAnalytics();

const page = usePage();
const tenant = computed(() => page.props.tenant);
const customer = computed(() => page.props.auth?.customer);
const languages = computed(() => page.props.languages ?? []);
const locale = computed(() => page.props.locale ?? 'pt_BR');
const t = (key) => page.props.translations?.[key] ?? key;

const brandColor = computed(() => tenant.value?.theme_primary_color || '#f4003a');

const switchLocale = (code) => {
    const url = new URL(window.location.href);
    url.searchParams.set('lang', code);
    window.location.href = url.toString();
};

const accountHref = computed(() =>
    customer.value
        ? route('app.conta.dashboard')
        : tenant.value
          ? route('tenant.conta.login', { tenant: tenant.value.slug })
          : route('app.conta.login'),
);
</script>

<template>
    <div
        class="public-menu min-h-screen bg-[var(--menu-bg)] font-menu text-gray-900"
        :style="{ '--primary': brandColor, '--menu-primary': brandColor }"
    >
        <header class="border-b border-gray-200 bg-white shadow-sm">
            <div class="mx-auto flex h-14 max-w-3xl items-center justify-between px-4">
                <Link
                    v-if="tenant"
                    :href="route('tenant.home', { tenant: tenant.slug })"
                    class="flex items-center gap-2 font-bold text-gray-900"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold text-white"
                        :style="{ backgroundColor: 'var(--menu-primary)' }"
                    >
                        {{ tenant.name?.charAt(0) }}
                    </span>
                    {{ tenant.name }}
                </Link>
                <Link v-else :href="route('app.conta.dashboard')" class="font-bold text-gray-900">
                    {{ t('app.name') }}
                </Link>
                <div class="flex items-center gap-2">
                    <select
                        v-if="tenant && languages.length > 1"
                        :value="locale"
                        class="rounded-full border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-600"
                        @change="switchLocale($event.target.value)"
                    >
                        <option v-for="lang in languages" :key="lang.code" :value="lang.code">
                            {{ lang.flag }} {{ lang.name }}
                        </option>
                    </select>
                    <Link
                        v-if="tenant?.guest_checkout_enabled"
                        :href="route('tenant.track.lookup', { tenant: tenant.slug })"
                        class="rounded-full px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100"
                    >
                        {{ t('nav.track') }}
                    </Link>
                    <Link
                        v-if="tenant"
                        :href="route('tenant.support', { tenant: tenant.slug })"
                        class="rounded-full px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100"
                    >
                        Falar com a loja
                    </Link>
                    <Link
                        :href="accountHref"
                        class="rounded-full px-3 py-1.5 text-sm font-semibold"
                        :style="{ color: 'var(--menu-primary)' }"
                    >
                        <template v-if="customer">{{ customer.name.split(' ')[0] }}</template>
                        <template v-else>{{ t('nav.account') }}</template>
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8">
            <slot />
        </main>

        <footer class="border-t border-gray-200 bg-white px-4 py-6">
            <p v-if="tenant" class="text-center text-xs font-medium text-gray-700">{{ tenant.name }}</p>
            <p v-if="!tenant" class="text-center text-xs text-gray-500">Conta única para todos os restaurantes da plataforma.</p>
            <LegalFooter class="mt-2" :tenant-slug="tenant?.slug" />
        </footer>
    </div>
</template>
