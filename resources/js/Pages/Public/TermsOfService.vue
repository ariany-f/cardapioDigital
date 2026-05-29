<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: PublicLayout });

defineProps({
    seo: Object,
    content: Object,
    tenantSlug: { type: String, default: null },
});

const backHref = (tenantSlug) =>
    tenantSlug ? route('tenant.home', { tenant: tenantSlug }) : route('marketing.landing');
</script>

<template>
    <SeoHead :seo="seo" />
    <Head :title="content.title" />

    <article class="prose prose-stone max-w-none prose-headings:font-display prose-h2:text-lg prose-p:text-sm prose-p:leading-relaxed">
        <Link
            :href="backHref(tenantSlug)"
            class="mb-6 inline-block text-sm font-medium no-underline hover:underline"
            style="color: var(--menu-primary)"
        >
            ← Voltar
        </Link>

        <h1 class="font-display text-2xl font-semibold text-stone-900">{{ content.title }}</h1>
        <p class="text-xs text-stone-500">{{ content.updated_at }}</p>
        <p class="mt-4 text-sm leading-relaxed text-stone-700">{{ content.intro }}</p>

        <section v-for="(section, index) in content.sections" :key="index" class="mt-8">
            <h2 class="text-base font-semibold text-stone-900">{{ section.title }}</h2>
            <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ section.body }}</p>
        </section>
    </article>
</template>
