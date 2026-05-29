<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    seo: { type: Object, default: null },
});

const hasSeo = computed(() => props.seo && props.seo.title);

const jsonLdScripts = computed(() => {
    if (!props.seo?.json_ld) return [];
    const items = Array.isArray(props.seo.json_ld) ? props.seo.json_ld : [props.seo.json_ld];
    return items.filter(Boolean);
});
</script>

<template>
    <Head v-if="hasSeo" :title="seo.title">
        <meta v-if="seo.description" head-key="description" name="description" :content="seo.description" />
        <meta v-if="seo.keywords" head-key="keywords" name="keywords" :content="seo.keywords" />
        <meta head-key="robots" name="robots" :content="seo.robots" />
        <link v-if="seo.canonical" head-key="canonical" rel="canonical" :href="seo.canonical" />

        <meta head-key="og:type" property="og:type" :content="seo.og?.type || 'website'" />
        <meta v-if="seo.og?.title" head-key="og:title" property="og:title" :content="seo.og.title" />
        <meta v-if="seo.og?.description" head-key="og:description" property="og:description" :content="seo.og.description" />
        <meta v-if="seo.og?.image" head-key="og:image" property="og:image" :content="seo.og.image" />
        <meta v-if="seo.og?.url" head-key="og:url" property="og:url" :content="seo.og.url" />
        <meta v-if="seo.og?.site_name" head-key="og:site_name" property="og:site_name" :content="seo.og.site_name" />

        <meta head-key="twitter:card" name="twitter:card" :content="seo.twitter?.card || 'summary_large_image'" />
        <meta v-if="seo.twitter?.title" head-key="twitter:title" name="twitter:title" :content="seo.twitter.title" />
        <meta
            v-if="seo.twitter?.description"
            head-key="twitter:description"
            name="twitter:description"
            :content="seo.twitter.description"
        />
        <meta v-if="seo.twitter?.image" head-key="twitter:image" name="twitter:image" :content="seo.twitter.image" />

        <meta
            v-if="seo.verification?.google"
            head-key="google-site-verification"
            name="google-site-verification"
            :content="seo.verification.google"
        />

        <component
            :is="'script'"
            v-for="(item, index) in jsonLdScripts"
            :key="'jsonld-' + index"
            type="application/ld+json"
            v-text="JSON.stringify(item)"
        />
    </Head>
</template>
