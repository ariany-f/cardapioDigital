<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    settings: Object,
    defaults: Object,
    hints: Object,
});

const form = useForm({ ...props.settings });

const titleLen = computed(() => (form.meta_title || '').length);
const descLen = computed(() => (form.meta_description || '').length);

const submit = () => form.put(route('platform.settings.seo.update'), { preserveScroll: true });
</script>

<template>
    <Head title="SEO — Plataforma" />

    <h1 class="text-2xl font-bold text-slate-900">SEO do site</h1>
    <p class="mt-1 max-w-2xl text-sm text-stone-500">
        Configure títulos, descrições e dados para o Google indexar a landing e os cardápios dos restaurantes.
        <a
            v-if="form.marketing_landing_enabled"
            :href="route('marketing.landing')"
            target="_blank"
            class="text-indigo-600 hover:underline"
        >Ver site</a>
        <span v-else class="text-amber-700">Landing desativada — visitantes em / vão para o login</span>
        ·
        <a href="/robots.txt" target="_blank" class="text-indigo-600 hover:underline">robots.txt</a>
        ·
        <a href="/sitemap.xml" target="_blank" class="text-indigo-600 hover:underline">sitemap.xml</a>
    </p>

    <form class="mt-8 max-w-3xl space-y-8" @submit.prevent="submit">
        <section class="admin-card space-y-4">
            <h2 class="text-lg font-semibold text-slate-900">Site institucional (landing /)</h2>

            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <input v-model="form.marketing_landing_enabled" type="checkbox" class="mt-0.5" />
                <span class="text-sm text-slate-800">
                    <span class="font-semibold">Landing pública ativa</span>
                    <span class="mt-1 block text-slate-600">
                        Quando desligada, a página inicial (planos, contato) fica indisponível e quem acessar /
                        é enviado ao login. Os cardápios dos restaurantes (/slug-da-loja) continuam normais.
                    </span>
                </span>
            </label>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Nome do site</label>
                <input v-model="form.site_name" class="admin-input" required />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">
                    Título (meta title)
                    <span class="font-normal" :class="titleLen > hints.title_length ? 'text-amber-600' : 'text-stone-400'">
                        {{ titleLen }}/{{ hints.title_length }} recomendado
                    </span>
                </label>
                <input v-model="form.meta_title" class="admin-input" required maxlength="120" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">
                    Descrição (meta description)
                    <span class="font-normal" :class="descLen > hints.description_length ? 'text-amber-600' : 'text-stone-400'">
                        {{ descLen }}/{{ hints.description_length }} recomendado
                    </span>
                </label>
                <textarea v-model="form.meta_description" class="admin-input" rows="3" required maxlength="320" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Palavras-chave (opcional)</label>
                <input v-model="form.meta_keywords" class="admin-input" placeholder="cardápio digital, pedidos online, delivery" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">URL canônica</label>
                <input v-model="form.canonical_url" type="url" class="admin-input" :placeholder="defaults.canonical_url" />
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.robots_index" type="checkbox" class="rounded border-slate-300" />
                Permitir indexação no Google (index, follow)
            </label>
        </section>

        <section class="admin-card space-y-4">
            <h2 class="text-lg font-semibold text-slate-900">Redes sociais (Open Graph)</h2>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Título OG (opcional)</label>
                <input v-model="form.og_title" class="admin-input" maxlength="120" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Descrição OG (opcional)</label>
                <textarea v-model="form.og_description" class="admin-input" rows="2" maxlength="320" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Imagem OG (URL ou caminho)</label>
                <input v-model="form.og_image_path" class="admin-input" placeholder="images/og-landing.jpg ou https://..." />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Twitter card</label>
                <select v-model="form.twitter_card" class="admin-input">
                    <option value="summary_large_image">Imagem grande</option>
                    <option value="summary">Resumo</option>
                </select>
            </div>
        </section>

        <section class="admin-card space-y-4">
            <h2 class="text-lg font-semibold text-slate-900">Google</h2>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Código de verificação Search Console</label>
                <input v-model="form.google_site_verification" class="admin-input" placeholder="conteúdo da meta google-site-verification" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Google Analytics (G-XXXXXXXX)</label>
                <input v-model="form.google_analytics_id" class="admin-input" placeholder="G-XXXXXXXXXX" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.json_ld_enabled" type="checkbox" class="rounded border-slate-300" />
                Incluir dados estruturados (JSON-LD) na landing
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-stone-600">Nome da organização</label>
                    <input v-model="form.organization_name" class="admin-input" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-stone-600">Logo (URL ou caminho)</label>
                    <input v-model="form.organization_logo_path" class="admin-input" />
                </div>
            </div>
        </section>

        <section class="admin-card space-y-4">
            <h2 class="text-lg font-semibold text-slate-900">Padrão dos cardápios (restaurantes)</h2>
            <p class="text-sm text-stone-500">
                Use <code class="rounded bg-stone-100 px-1">{name}</code> no título para o nome do restaurante. Cada loja pode ter SEO próprio em Restaurantes → SEO.
            </p>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Modelo de título</label>
                <input v-model="form.tenant_title_template" class="admin-input" required />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Descrição padrão (se o restaurante não tiver)</label>
                <textarea v-model="form.tenant_meta_description_fallback" class="admin-input" rows="2" maxlength="320" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Imagem OG padrão dos restaurantes</label>
                <input v-model="form.tenant_og_image_path" class="admin-input" placeholder="storage/... ou URL" />
            </div>
        </section>

        <button type="submit" class="admin-btn-primary" :disabled="form.processing">Salvar SEO</button>
    </form>
</template>
