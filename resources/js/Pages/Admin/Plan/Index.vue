<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePlanFeatures } from '@/composables/usePlanFeatures';
import { Head, useForm, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    currentPlan: Object,
    availablePlans: Array,
    requests: Array,
    hasPendingRequest: Boolean,
    statusLabels: Object,
});

const page = usePage();
const tenant = page.props.tenant;
const { listFeatures } = usePlanFeatures();

const form = useForm({
    requested_plan_id: '',
    message: '',
});

const formatPrice = (value) =>
    parseFloat(value ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const formatDate = (iso) =>
    iso
        ? new Date(iso).toLocaleString('pt-BR', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '—';

const statusClass = (status) => {
    if (status === 'pending') return 'bg-amber-100 text-amber-900';
    if (status === 'approved') return 'bg-green-100 text-green-900';
    return 'bg-red-100 text-red-900';
};

const selectablePlans = () =>
    props.availablePlans.filter((p) => p.id !== props.currentPlan?.id);

const submit = () => {
    form.post(route('tenant.admin.plan-change-requests.store', { tenant: tenant.slug }), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Plano e assinatura" />

    <h1 class="text-2xl font-bold text-slate-900">Plano e assinatura</h1>
    <p class="mt-1 text-sm text-slate-500">
        Solicite a migração para outro plano. A alteração só entra em vigor após aprovação da plataforma.
    </p>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Plano atual</h2>
            <p v-if="currentPlan" class="mt-2 text-2xl font-bold text-slate-900">
                {{ currentPlan.name }}
                <span class="text-base font-normal text-slate-500">{{ formatPrice(currentPlan.price_monthly) }}/mês</span>
            </p>
            <p v-else class="mt-2 text-sm text-slate-500">Nenhum plano vinculado.</p>

            <ul v-if="currentPlan?.features_json" class="mt-4 space-y-1.5 text-sm text-slate-700">
                <li v-for="feature in listFeatures(currentPlan.features_json)" :key="feature.key" class="flex justify-between gap-2">
                    <span class="text-slate-600">{{ feature.label }}</span>
                    <span class="font-medium">{{ feature.value }}</span>
                </li>
            </ul>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900">Solicitar migração</h2>

            <p v-if="hasPendingRequest" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                Você já tem uma solicitação pendente. Aguarde a análise da plataforma.
            </p>

            <form v-else class="mt-4 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Novo plano *</label>
                    <select v-model="form.requested_plan_id" class="platform-input w-full" required>
                        <option value="" disabled>Selecione…</option>
                        <option v-for="plan in selectablePlans()" :key="plan.id" :value="plan.id">
                            {{ plan.name }} — {{ formatPrice(plan.price_monthly) }}/mês
                        </option>
                    </select>
                    <p v-if="form.errors.requested_plan_id" class="mt-1 text-sm text-red-600">
                        {{ form.errors.requested_plan_id }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Mensagem (opcional)</label>
                    <textarea
                        v-model="form.message"
                        rows="3"
                        class="platform-input w-full"
                        placeholder="Ex.: precisamos do módulo de entregadores e PDV."
                    />
                    <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                </div>

                <button
                    type="submit"
                    class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 disabled:opacity-50"
                    :disabled="form.processing || !selectablePlans().length"
                >
                    {{ form.processing ? 'Enviando…' : 'Enviar solicitação' }}
                </button>
            </form>
        </section>
    </div>

    <section v-if="requests?.length" class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-base font-semibold text-slate-900">Histórico de solicitações</h2>
        <ul class="mt-4 divide-y divide-slate-100">
            <li v-for="req in requests" :key="req.id" class="py-4 first:pt-0">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-medium text-slate-900">
                            {{ req.current_plan?.name ?? '—' }}
                            →
                            {{ req.requested_plan?.name }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">{{ formatDate(req.created_at) }}</p>
                        <p v-if="req.message" class="mt-2 text-sm text-slate-600">{{ req.message }}</p>
                        <p v-if="req.admin_notes" class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            Resposta da plataforma: {{ req.admin_notes }}
                        </p>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(req.status)">
                        {{ statusLabels[req.status] ?? req.status }}
                    </span>
                </div>
            </li>
        </ul>
    </section>
</template>
