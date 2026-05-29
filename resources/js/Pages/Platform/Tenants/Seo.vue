<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    tenant: Object,
    seo: Object,
    preview: Object,
    titleTemplate: String,
});

const form = useForm({
    meta_title: props.seo.meta_title ?? '',
    meta_description: props.seo.meta_description ?? '',
    meta_keywords: props.seo.meta_keywords ?? '',
    canonical_url: props.seo.canonical_url ?? '',
    og_image_path: props.seo.og_image_path ?? '',
    robots_index: props.seo.robots_index ?? true,
    google_site_verification: props.seo.google_site_verification ?? '',
    google_analytics_id: props.seo.google_analytics_id ?? '',
    json_ld_enabled: props.seo.json_ld_enabled ?? true,
});

const submit = () =>
    form.put(route('platform.tenants.seo.update', props.tenant.id), { preserveScroll: true });

const previewTitle = computed(() => form.meta_title || props.preview?.title);
</script>

<template>
    <Head :title="`SEO — ${tenant.name}`" />

    <Link :href="route('platform.tenants.show', tenant.id)" class="text-sm text-indigo-600 hover:underline">
        ← {{ tenant.name }}
    </Link>

    <h1 class="mt-4 text-2xl font-bold text-slate-900">SEO do restaurante</h1>
    <p class="mt-1 text-sm text-stone-500">
        Cardápio público:
        <a :href="`/${tenant.slug}`" target="_blank" class="text-indigo-600 hover:underline">/{{ tenant.slug }}</a>
    </p>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <form class="admin-card space-y-4" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Título personalizado</label>
                <input v-model="form.meta_title" class="admin-input" :placeholder="titleTemplate.replace('{name}', tenant.name)" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Meta description</label>
                <textarea
                    v-model="form.meta_description"
                    class="admin-input"
                    rows="3"
                    maxlength="320"
                    :placeholder="tenant.public_description || 'Descrição para o Google'"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Palavras-chave</label>
                <input v-model="form.meta_keywords" class="admin-input" placeholder="pizza, delivery, centro" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">URL canônica</label>
                <input v-model="form.canonical_url" type="url" class="admin-input" :placeholder="`https://.../${tenant.slug}`" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Imagem OG</label>
                <input v-model="form.og_image_path" class="admin-input" :placeholder="tenant.cover_image_path || 'capa ou URL'" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.robots_index" type="checkbox" class="rounded border-slate-300" />
                Indexar no Google
            </label>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Verificação Google (opcional)</label>
                <input v-model="form.google_site_verification" class="admin-input" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Google Analytics desta loja</label>
                <input v-model="form.google_analytics_id" class="admin-input" placeholder="G-XXXXXXXX" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.json_ld_enabled" type="checkbox" class="rounded border-slate-300" />
                Dados estruturados Restaurant (JSON-LD)
            </label>
            <button type="submit" class="admin-btn-primary w-full" :disabled="form.processing">Salvar</button>
        </form>

        <aside class="admin-card">
            <h2 class="text-sm font-semibold text-stone-800">Prévia no Google</h2>
            <div class="mt-4 rounded-lg border border-stone-200 bg-white p-4">
                <p class="truncate text-sm text-[#1a0dab]">{{ previewTitle }}</p>
                <p class="truncate text-xs text-[#006621]">{{ preview?.canonical }}</p>
                <p class="mt-1 line-clamp-2 text-sm text-stone-600">
                    {{ form.meta_description || preview?.description }}
                </p>
            </div>
            <p class="mt-4 text-xs text-stone-500">Robots: {{ form.robots_index ? 'index, follow' : 'noindex' }}</p>
        </aside>
    </div>
</template>
