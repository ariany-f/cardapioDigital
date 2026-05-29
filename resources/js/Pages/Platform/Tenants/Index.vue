<script setup>
import TenantFormFields from '@/Components/Platform/TenantFormFields.vue';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { emptyTenantEditForm, emptyTenantForm, tenantFormFromModel } from '@/composables/platformForms';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    tenants: Object,
    plans: Array,
    languages: { type: Array, default: () => [] },
    currencies: { type: Array, default: () => [] },
    timezones: { type: Array, default: () => [] },
    selectedTenant: Object,
    recentPayments: Array,
    editingTenant: Object,
    creating: Boolean,
});

const formatMoney = (value) =>
    parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const formatDate = (value) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('pt-BR');
};

const createForm = useForm(emptyTenantForm(props.plans, props.languages));

const editForm = useForm(emptyTenantEditForm());

watch(
    () => props.editingTenant,
    (t) => {
        if (t) {
            editForm.defaults(tenantFormFromModel(t)).reset();
        }
    },
    { immediate: true },
);

const suspendForm = useForm({ suspension_reason: '' });

const submitCreate = () => createForm.post(route('platform.tenants.store'));
const submitEdit = () => editForm.put(route('platform.tenants.update', props.editingTenant.id));
const submitSuspend = () => suspendForm.post(route('platform.tenants.suspend', props.selectedTenant.id));
const activate = (id) => router.post(route('platform.tenants.activate', id));

const statusLabel = (status) => (status === 'active' ? 'Ativo' : 'Suspenso');

const featureEnabled = (tenant, key) => (tenant?.settings_json?.[key] ?? true) !== false;
const motoboysEnabled = (tenant) => featureEnabled(tenant, 'motoboys_enabled');
const posEnabled = (tenant) => featureEnabled(tenant, 'pos_enabled');
const kdsEnabled = (tenant) => featureEnabled(tenant, 'kds_enabled');

const formatRestaurantRating = (summary) => {
    if (!summary?.count || summary.restaurant == null) {
        return '—';
    }

    return `★ ${summary.restaurant} (${summary.count})`;
};
</script>

<template>
    <Head title="Restaurantes" />

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Restaurantes</h1>
        <Link :href="route('platform.tenants.create')" class="platform-btn-primary">
            Novo restaurante
        </Link>
    </div>

    <form v-if="creating" class="platform-card mt-6 space-y-4" @submit.prevent="submitCreate">
        <h2 class="text-base font-semibold text-slate-900">Cadastrar restaurante</h2>
        <TenantFormFields
            :form="createForm"
            show-slug
            show-plan
            :plans="plans"
            :languages="languages"
            :currencies="currencies"
            :timezones="timezones"
        />
        <div class="border-t border-slate-100 pt-4">
            <button type="submit" class="platform-btn-primary" :disabled="createForm.processing">Salvar</button>
        </div>
    </form>

    <div class="mt-6 platform-table-wrap">
        <table class="platform-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Plano</th>
                    <th>Avaliação</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="t in tenants.data" :key="t.id">
                    <td class="font-medium">{{ t.name }}</td>
                    <td class="text-slate-500">/{{ t.slug }}</td>
                    <td>
                        <span class="rounded-full px-2 py-0.5 text-xs" :class="t.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                            {{ statusLabel(t.status) }}
                        </span>
                        <span
                            v-if="!motoboysEnabled(t)"
                            class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600"
                            title="Módulo de entregadores desativado"
                        >
                            Sem entregadores
                        </span>
                        <span
                            v-if="!posEnabled(t)"
                            class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600"
                            title="PDV desativado"
                        >
                            Sem PDV
                        </span>
                        <span
                            v-if="!kdsEnabled(t)"
                            class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600"
                            title="KDS desativado"
                        >
                            Sem KDS
                        </span>
                    </td>
                    <td>{{ t.active_subscription?.plan?.name ?? '—' }}</td>
                    <td class="whitespace-nowrap text-amber-700" :title="t.rating_summary?.count ? `${t.rating_summary.count} avaliações` : 'Sem avaliações'">
                        {{ formatRestaurantRating(t.rating_summary) }}
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <Link
                            :href="route('tenant.admin.dashboard', { tenant: t.slug })"
                            class="font-medium text-orange-600 hover:underline"
                        >
                            Painel
                        </Link>
                        <Link :href="route('platform.tenants.show', t.id)" class="ml-2 text-indigo-600 hover:underline">Ver</Link>
                        <Link :href="route('platform.tenants.edit', t.id)" class="ml-2 text-slate-600 hover:underline">Editar</Link>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="selectedTenant" class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="platform-card">
            <h2 class="font-semibold">{{ selectedTenant.name }}</h2>
            <p v-if="selectedTenant.legal_name" class="text-sm text-slate-600">{{ selectedTenant.legal_name }}</p>
            <div class="mt-2 flex flex-wrap gap-1.5 text-xs">
                <span
                    class="rounded-full px-2 py-0.5"
                    :class="motoboysEnabled(selectedTenant) ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600'"
                >
                    {{
                        motoboysEnabled(selectedTenant)
                            ? 'Entregadores ativo'
                            : 'Entregadores desativado'
                    }}
                </span>
                <span
                    class="rounded-full px-2 py-0.5"
                    :class="posEnabled(selectedTenant) ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600'"
                >
                    {{ posEnabled(selectedTenant) ? 'PDV ativo' : 'PDV desativado' }}
                </span>
                <span
                    class="rounded-full px-2 py-0.5"
                    :class="kdsEnabled(selectedTenant) ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600'"
                >
                    {{ kdsEnabled(selectedTenant) ? 'KDS ativo' : 'KDS desativado' }}
                </span>
            </div>
            <div v-if="selectedTenant.rating_summary?.count" class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Restaurante</p>
                    <p class="mt-0.5 text-xl font-bold text-amber-600">{{ selectedTenant.rating_summary.restaurant ?? '—' }}</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Pedido</p>
                    <p class="mt-0.5 text-xl font-bold text-amber-600">{{ selectedTenant.rating_summary.order ?? '—' }}</p>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Entrega</p>
                    <p class="mt-0.5 text-xl font-bold text-amber-600">{{ selectedTenant.rating_summary.delivery ?? '—' }}</p>
                </div>
                <p class="sm:col-span-3 text-center text-xs text-slate-500">
                    {{ selectedTenant.rating_summary.count }} avaliação(ões) públicas
                </p>
            </div>
            <p v-else class="mt-4 text-sm text-slate-400">Sem avaliações públicas ainda.</p>

            <dl class="mt-3 space-y-1 text-xs text-slate-600">
                <div v-if="selectedTenant.document_number">
                    <dt class="inline font-medium text-slate-500">CNPJ/CPF:</dt>
                    <dd class="inline ml-1">{{ selectedTenant.document_number }}</dd>
                </div>
                <div v-if="selectedTenant.email">
                    <dt class="inline font-medium text-slate-500">E-mail:</dt>
                    <dd class="inline ml-1">{{ selectedTenant.email }}</dd>
                </div>
                <div v-if="selectedTenant.phone">
                    <dt class="inline font-medium text-slate-500">Tel:</dt>
                    <dd class="inline ml-1">{{ selectedTenant.phone }}</dd>
                </div>
            </dl>
            <div class="mt-4">
                <Link
                    :href="route('tenant.admin.dashboard', { tenant: selectedTenant.slug })"
                    class="inline-flex items-center rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700"
                >
                    Painel completo do restaurante
                </Link>
            </div>
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-600">
                <span class="text-slate-400">Atalhos na plataforma:</span>
                <Link :href="route('platform.tenants.products.index', selectedTenant.id)" class="text-indigo-600 hover:underline">
                    Produtos
                </Link>
                <Link :href="route('platform.tenants.categories.index', selectedTenant.id)" class="text-indigo-600 hover:underline">
                    Categorias
                </Link>
                <Link :href="route('platform.tenants.branches.index', selectedTenant.id)" class="text-indigo-600 hover:underline">
                    Filiais
                </Link>
                <Link :href="route('platform.tenants.seo.edit', selectedTenant.id)" class="text-indigo-600 hover:underline">
                    SEO
                </Link>
            </div>
            <div class="mt-4 flex gap-2">
                <button v-if="selectedTenant.status === 'active'" type="button" class="rounded bg-red-600 px-3 py-1 text-sm text-white" @click="submitSuspend">
                    Suspender
                </button>
                <button v-else type="button" class="rounded bg-green-600 px-3 py-1 text-sm text-white" @click="activate(selectedTenant.id)">
                    Reativar
                </button>
            </div>
        </div>
        <div class="platform-card">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold">Pagamentos</h3>
                <Link
                    :href="route('platform.payments.create', { tenant_id: selectedTenant.id })"
                    class="text-sm text-indigo-600 hover:underline"
                >
                    + Registrar
                </Link>
            </div>
            <p class="mt-1 text-xs text-slate-500">
                Assinatura:
                <span
                    :class="selectedTenant.active_subscription?.payment_status === 'paid' ? 'text-green-700' : 'text-amber-700'"
                >
                    {{ selectedTenant.active_subscription?.payment_status === 'paid' ? 'Paga' : 'Pendente' }}
                </span>
                · {{ selectedTenant.active_subscription?.plan?.name ?? '—' }}
            </p>
            <ul v-if="recentPayments?.length" class="mt-3 divide-y divide-slate-100 text-sm">
                <li v-for="p in recentPayments" :key="p.id" class="flex justify-between py-2">
                    <span class="text-slate-600">{{ formatDate(p.paid_at) }}</span>
                    <span class="font-medium text-green-700">{{ formatMoney(p.amount) }}</span>
                </li>
            </ul>
            <p v-else class="mt-3 text-sm text-slate-400">Nenhum pagamento ainda.</p>
            <Link
                :href="route('platform.payments.index', { tenant_id: selectedTenant.id })"
                class="mt-3 inline-block text-sm text-indigo-600 hover:underline"
            >
                Ver todos os pagamentos →
            </Link>
        </div>
    </div>

    <form v-if="editingTenant" class="platform-card mt-6 space-y-4" @submit.prevent="submitEdit">
        <h2 class="text-base font-semibold text-slate-900">Editar restaurante — {{ editingTenant.name }}</h2>
        <TenantFormFields
            :form="editForm"
            :languages="languages"
            :currencies="currencies"
            :timezones="timezones"
            :plans="plans"
            :plan-motoboys-included="editingTenant?.plan_motoboys_included ?? true"
            :motoboys-disable-blocked="editingTenant?.motoboys_disable_blocked"
            :motoboy-deliveries-in-progress-count="editingTenant?.motoboy_deliveries_in_progress_count ?? 0"
        />
        <div class="flex gap-3 border-t border-slate-100 pt-4">
            <Link :href="route('platform.tenants.index')" class="platform-btn-secondary">Voltar</Link>
            <button type="submit" class="platform-btn-primary" :disabled="editForm.processing">Salvar alterações</button>
        </div>
    </form>
</template>
