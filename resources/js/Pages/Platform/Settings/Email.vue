<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    settings: Object,
    envFallback: Object,
    suppressesInLocal: Boolean,
});

const form = useForm({
    enabled: props.settings.enabled,
    host: props.settings.host,
    port: props.settings.port || 587,
    username: props.settings.username,
    password: '',
    encryption: props.settings.encryption || '',
    from_address: props.settings.from_address,
    from_name: props.settings.from_name,
});

const testForm = useForm({
    to: '',
});

const showPassword = ref(false);

const submit = () =>
    form.put(route('platform.settings.email.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.password = '';
        },
    });

const sendTest = () =>
    testForm.post(route('platform.settings.email.test'), {
        preserveScroll: true,
    });
</script>

<template>
    <Head title="E-mail (SMTP)" />

    <h1 class="text-2xl font-bold text-slate-900">E-mail (SMTP)</h1>
    <p class="mt-1 text-sm text-stone-500">
        Configure o servidor de envio usado por contato da landing, notificações e demais e-mails do sistema.
    </p>

    <p v-if="suppressesInLocal" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Em ambiente local, e-mails automáticos ficam suprimidos por padrão. O botão &quot;Enviar teste&quot; força o envio
        para validar o SMTP.
    </p>

  <p v-if="!settings.enabled" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        Sem configuração ativa, o sistema usa o <code class="text-xs">.env</code>
        (mailer: {{ envFallback.mailer }}, host: {{ envFallback.host || '—' }}, remetente: {{ envFallback.from_address }}).
    </p>

    <form class="mt-6 max-w-2xl space-y-4 admin-card" @submit.prevent="submit">
        <label class="flex items-center gap-2 text-sm font-medium text-slate-800">
            <input v-model="form.enabled" type="checkbox" class="rounded border-slate-300" />
            Usar SMTP configurado aqui (substitui .env em produção)
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-stone-600">Host SMTP *</label>
                <input v-model="form.host" type="text" class="admin-input" placeholder="smtp.exemplo.com" />
                <p v-if="form.errors.host" class="mt-1 text-xs text-red-600">{{ form.errors.host }}</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Porta</label>
                <input v-model.number="form.port" type="number" class="admin-input" min="1" max="65535" />
                <p v-if="form.errors.port" class="mt-1 text-xs text-red-600">{{ form.errors.port }}</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Criptografia</label>
                <select v-model="form.encryption" class="admin-input">
                    <option value="">Nenhuma (STARTTLS automático)</option>
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Usuário</label>
                <input v-model="form.username" type="text" class="admin-input" autocomplete="off" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">
                    Senha
                    <span v-if="settings.password_configured" class="font-normal text-stone-500">(deixe em branco para manter)</span>
                </label>
                <div class="flex gap-2">
                    <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="admin-input flex-1"
                        autocomplete="new-password"
                    />
                    <button type="button" class="admin-btn-secondary shrink-0 text-xs" @click="showPassword = !showPassword">
                        {{ showPassword ? 'Ocultar' : 'Mostrar' }}
                    </button>
                </div>
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Remetente (e-mail) *</label>
                <input v-model="form.from_address" type="email" class="admin-input" placeholder="noreply@seudominio.com" />
                <p v-if="form.errors.from_address" class="mt-1 text-xs text-red-600">{{ form.errors.from_address }}</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-stone-600">Nome do remetente</label>
                <input v-model="form.from_name" type="text" class="admin-input" placeholder="App Cardápio" />
            </div>
        </div>

        <div class="flex flex-wrap gap-3 border-t border-slate-100 pt-4">
            <button type="submit" class="admin-btn-primary" :disabled="form.processing">Salvar configuração</button>
        </div>
    </form>

    <section class="mt-8 max-w-2xl admin-card">
        <h2 class="text-lg font-semibold text-slate-900">Enviar e-mail de teste</h2>
        <p class="mt-1 text-sm text-stone-500">Salve antes de testar. O envio usa a configuração SMTP ativa.</p>

        <p v-if="testForm.errors.test" class="mt-3 text-sm text-red-600">{{ testForm.errors.test }}</p>

        <form class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="sendTest">
            <div class="flex-1">
                <label class="mb-1 block text-xs font-medium text-stone-600">Destinatário (opcional)</label>
                <input
                    v-model="testForm.to"
                    type="email"
                    class="admin-input"
                    placeholder="Usa seu e-mail de login se vazio"
                />
            </div>
            <button type="submit" class="admin-btn-secondary shrink-0" :disabled="testForm.processing || !settings.enabled">
                Enviar teste
            </button>
        </form>
    </section>
</template>
