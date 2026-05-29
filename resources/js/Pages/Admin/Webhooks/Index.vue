<script setup>
import AdminListSearch from '@/Components/Admin/AdminListSearch.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ tokens: Array, webhookUrl: String });
const page = usePage();
const tenant = page.props.tenant;
const revealed = ref({});

const form = useForm({ name: '' });

const submit = () =>
    form.post(route('tenant.admin.webhooks.store', { tenant: tenant.slug }), {
        onSuccess: () => form.reset(),
    });

const toggle = (id, active) =>
    router.put(route('tenant.admin.webhooks.update', { tenant: tenant.slug, webhookToken: id }), {
        is_active: active,
    });

const rotate = (id) => {
    if (!confirm('Rotacionar token? Integrações antigas deixarão de funcionar.')) return;
    router.post(route('tenant.admin.webhooks.rotate', { tenant: tenant.slug, webhookToken: id }));
};

const remove = (id) => {
    if (!confirm('Remover este token?')) return;
    router.delete(route('tenant.admin.webhooks.destroy', { tenant: tenant.slug, webhookToken: id }));
};
</script>

<template>
    <Head title="Webhooks de entrega" />
    <h1 class="admin-page-title">Webhooks de entrega</h1>
    <p class="mt-1 text-sm text-stone-500">Tokens para integrações externas atualizarem status de entrega.</p>

    <div class="admin-card mt-6 max-w-2xl">
        <p class="text-sm text-stone-600">Endpoint</p>
        <code class="mt-1 block break-all rounded-lg bg-stone-100 px-3 py-2 text-sm">{{ webhookUrl }}</code>
        <p class="mt-2 text-xs text-stone-500">Header: <strong>X-Tenant-Token</strong> · Body: order_number, status, confirmation_code (ao entregar)</p>
    </div>

    <form class="admin-card mt-6 max-w-md space-y-3" @submit.prevent="submit">
        <h2 class="font-semibold">Novo token</h2>
        <input v-model="form.name" class="admin-input" placeholder="Nome (ex: ERP parceiro)" required />
        <button type="submit" class="admin-btn-primary" :disabled="form.processing">Gerar token</button>
    </form>

    <AdminListSearch
        :href="route('tenant.admin.webhooks.index', { tenant: tenant.slug })"
        :filters="filters"
        placeholder="Buscar token..."
    />

    <ul class="mt-6 space-y-3">
        <li v-for="t in tokens" :key="t.id" class="admin-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-semibold">{{ t.name }}</p>
                    <p class="text-xs text-stone-500">{{ t.type }} · {{ new Date(t.created_at).toLocaleString('pt-BR') }}</p>
                    <code v-if="revealed[t.id]" class="mt-2 block break-all text-xs">{{ t.token }}</code>
                    <button v-else type="button" class="mt-2 text-xs text-orange-600 underline" @click="revealed[t.id] = true">
                        Mostrar token
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="admin-btn-secondary text-sm" @click="toggle(t.id, !t.is_active)">
                        {{ t.is_active ? 'Desativar' : 'Ativar' }}
                    </button>
                    <button type="button" class="admin-btn-secondary text-sm" @click="rotate(t.id)">Rotacionar</button>
                    <button type="button" class="text-sm text-red-600" @click="remove(t.id)">Excluir</button>
                </div>
            </div>
        </li>
    </ul>
</template>
