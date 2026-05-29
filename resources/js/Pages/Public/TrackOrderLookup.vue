<script setup>
import CommunicationNotice from '@/Components/Public/CommunicationNotice.vue';
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineOptions({ layout: PublicLayout });

defineProps({
    seo: Object,
    tenantSlug: String,
});

const page = usePage();
const t = (key) => page.props.translations?.[key] ?? key;

const form = useForm({
    order_number: '',
    code: '',
    phone: '',
    email: '',
});

const submit = () => {
    form.post(route('tenant.track.lookup.store', { tenant: page.props.tenant?.slug ?? '' }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <SeoHead :seo="seo" />

    <div class="mx-auto max-w-md">
        <h1 class="text-2xl font-bold text-stone-900">{{ t('nav.track') }}</h1>
        <p class="mt-2 text-sm text-stone-600">{{ t('order.access.lookup_hint') }}</p>
        <CommunicationNotice class="mt-4" compact :show-alerts="false" />

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-stone-500">
                    {{ t('order.access.code_label') }}
                </label>
                <input
                    v-model="form.code"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    autocomplete="one-time-code"
                    class="menu-input mt-1 font-mono text-lg tracking-[0.35em]"
                    placeholder="000000"
                    required
                />
                <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
            </div>

            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-stone-500">
                    {{ t('order.access.order_number_optional') }}
                </label>
                <input
                    v-model="form.order_number"
                    type="text"
                    class="menu-input mt-1"
                    :placeholder="t('order.access.order_number_placeholder')"
                />
            </div>

            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-stone-500">
                    {{ t('order.access.phone_label') }}
                </label>
                <input
                    v-model="form.phone"
                    type="tel"
                    inputmode="tel"
                    autocomplete="tel"
                    class="menu-input mt-1"
                    placeholder="(11) 99999-9999"
                />
            </div>

            <p class="text-center text-xs text-stone-400">{{ t('order.access.or') }}</p>

            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-stone-500">
                    {{ t('order.access.email_label') }}
                </label>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    class="menu-input mt-1"
                    placeholder="seu@email.com"
                />
            </div>

            <p class="text-xs text-stone-500">{{ t('order.access.contact_required') }}</p>

            <button
                type="submit"
                class="w-full rounded-2xl py-3 text-sm font-semibold text-white disabled:opacity-50"
                style="background: var(--menu-primary)"
                :disabled="form.processing || form.code.length < 6"
            >
                {{ t('order.access.submit') }}
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-stone-500">
            <Link
                :href="route('tenant.home', { tenant: tenantSlug })"
                class="font-medium"
                style="color: var(--menu-primary)"
            >
                ← {{ t('public.menu_title') }}
            </Link>
        </p>
    </div>
</template>
