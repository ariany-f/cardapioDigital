<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    settings: Object,
    envFallback: Object,
    activeDisk: String,
});

const form = useForm({
    enabled: props.settings.enabled,
    key: props.settings.key,
    secret: '',
    region: props.settings.region || 'us-east-1',
    bucket: props.settings.bucket,
    url: props.settings.url,
    endpoint: props.settings.endpoint,
    use_path_style_endpoint: props.settings.use_path_style_endpoint,
});

const testForm = useForm({});

const showSecret = ref(false);

const submit = () =>
    form.put(route('platform.settings.storage.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.secret = '';
        },
    });

const testConnection = () =>
    testForm.post(route('platform.settings.storage.test'), {
        preserveScroll: true,
    });
</script>

<template>
    <Head title="Armazenamento (S3)" />

    <h1 class="text-2xl font-bold text-slate-900">Armazenamento (S3)</h1>
    <p class="mt-1 text-sm text-stone-500">
        Imagens de produtos, banners, logos e capas de filiais. Quando ativo, novos uploads vão para o bucket configurado.
    </p>

    <p v-if="!settings.enabled" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        Sem configuração ativa, o sistema usa disco local
        <code class="text-xs">public</code>
        ({{ envFallback.disk === 's3' ? 'S3 via .env' : 'storage/app/public' }}).
        Disco em uso agora: <strong>{{ activeDisk }}</strong>.
    </p>

    <p
        v-else
        class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
    >
        S3 ativo — bucket <strong>{{ settings.bucket }}</strong> ({{ settings.region }}). Disco em uso:
        <strong>{{ activeDisk }}</strong>.
    </p>

    <form class="admin-card mt-6 max-w-2xl space-y-4" @submit.prevent="submit">
        <label class="flex items-center gap-2 text-sm font-medium text-slate-800">
            <input v-model="form.enabled" type="checkbox" class="rounded border-slate-300" />
            Usar S3 configurado aqui (substitui .env em produção)
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-stone-600">Access Key ID *</label>
                <input v-model="form.key" type="text" class="admin-input" autocomplete="off" />
                <p v-if="form.errors.key" class="mt-1 text-xs text-red-600">{{ form.errors.key }}</p>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-stone-600">
                    Secret Access Key
                    <span v-if="settings.secret_configured" class="font-normal text-stone-500">(deixe em branco para manter)</span>
                </label>
                <div class="flex gap-2">
                    <input
                        v-model="form.secret"
                        :type="showSecret ? 'text' : 'password'"
                        class="admin-input flex-1"
                        autocomplete="new-password"
                    />
                    <button type="button" class="admin-btn-secondary shrink-0 text-xs" @click="showSecret = !showSecret">
                        {{ showSecret ? 'Ocultar' : 'Mostrar' }}
                    </button>
                </div>
                <p v-if="form.errors.secret" class="mt-1 text-xs text-red-600">{{ form.errors.secret }}</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Região *</label>
                <input v-model="form.region" type="text" class="admin-input" placeholder="us-east-1" />
                <p v-if="form.errors.region" class="mt-1 text-xs text-red-600">{{ form.errors.region }}</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Bucket *</label>
                <input v-model="form.bucket" type="text" class="admin-input" placeholder="meu-bucket" />
                <p v-if="form.errors.bucket" class="mt-1 text-xs text-red-600">{{ form.errors.bucket }}</p>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-stone-600">URL pública (opcional)</label>
                <input
                    v-model="form.url"
                    type="url"
                    class="admin-input"
                    placeholder="https://cdn.seudominio.com ou URL do bucket"
                />
                <p class="mt-1 text-xs text-stone-500">CDN ou domínio customizado para exibir imagens no cardápio.</p>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-stone-600">Endpoint (opcional)</label>
                <input
                    v-model="form.endpoint"
                    type="url"
                    class="admin-input"
                    placeholder="https://... (Cloudflare R2, MinIO, etc.)"
                />
            </div>

            <div class="sm:col-span-2">
                <label class="flex items-start gap-2 text-sm text-slate-700">
                    <input v-model="form.use_path_style_endpoint" type="checkbox" class="mt-0.5 rounded border-slate-300" />
                    <span>Path-style endpoint (comum em R2/MinIO)</span>
                </label>
            </div>
        </div>

        <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-4">
            <button type="submit" class="admin-btn-primary" :disabled="form.processing">Salvar configuração</button>
        </div>
    </form>

    <section class="admin-card mt-8 max-w-2xl">
        <h2 class="text-lg font-semibold text-slate-900">Testar conexão</h2>
        <p class="mt-1 text-sm text-stone-500">
            Salve antes de testar. O sistema grava e remove um arquivo temporário no bucket.
        </p>

        <p v-if="testForm.errors.test" class="mt-3 text-sm text-red-600">{{ testForm.errors.test }}</p>

        <button
            type="button"
            class="admin-btn-secondary mt-4"
            :disabled="testForm.processing || !settings.enabled"
            @click="testConnection"
        >
            {{ testForm.processing ? 'Testando...' : 'Testar conexão S3' }}
        </button>
    </section>
</template>
