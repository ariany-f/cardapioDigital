<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    tenants: Array,
    selectedTenant: Object,
});

const form = useForm({
    tenant_id: props.selectedTenant?.id ?? '',
    amount: '',
    reference: '',
    notes: '',
    paid_at: new Date().toISOString().slice(0, 10),
});

watch(
    () => props.selectedTenant,
    (t) => {
        if (t) form.tenant_id = t.id;
    },
    { immediate: true },
);

const selected = computed(() => props.tenants.find((t) => t.id === Number(form.tenant_id)));

const suggestedAmount = computed(() => {
    const plan = selected.value?.active_subscription?.plan;
    return plan?.price_monthly ? parseFloat(plan.price_monthly) : null;
});

const applySuggested = () => {
    if (suggestedAmount.value != null) {
        form.amount = suggestedAmount.value;
    }
};

const submit = () => form.post(route('platform.payments.store'));
</script>

<template>
    <Head title="Registrar pagamento" />

    <Link :href="route('platform.payments.index')" class="text-sm text-slate-500 hover:text-slate-700">← Pagamentos</Link>

    <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900">Registrar pagamento</h1>
    <p class="mt-1 text-sm text-slate-500">Marca a assinatura ativa do restaurante como paga ao salvar.</p>

    <form class="mt-6 max-w-lg space-y-4 platform-card" @submit.prevent="submit">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Restaurante *</label>
            <select v-model="form.tenant_id" class="platform-input" required>
                <option value="" disabled>Selecione...</option>
                <option v-for="t in tenants" :key="t.id" :value="t.id">
                    {{ t.name }} ({{ t.status === 'active' ? 'ativo' : 'suspenso' }})
                </option>
            </select>
            <p v-if="form.errors.tenant_id" class="mt-1 text-xs text-red-600">{{ form.errors.tenant_id }}</p>
        </div>

        <div v-if="selected" class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
            <p>
                Plano:
                <strong>{{ selected.active_subscription?.plan?.name ?? 'Sem assinatura ativa' }}</strong>
            </p>
            <p v-if="selected.active_subscription" class="mt-1">
                Status pagamento:
                <span
                    class="font-medium"
                    :class="selected.active_subscription.payment_status === 'paid' ? 'text-green-700' : 'text-amber-700'"
                >
                    {{ selected.active_subscription.payment_status === 'paid' ? 'Pago' : 'Pendente' }}
                </span>
            </p>
            <button
                v-if="suggestedAmount"
                type="button"
                class="mt-2 text-xs text-indigo-600 hover:underline"
                @click="applySuggested"
            >
                Usar valor do plano ({{ suggestedAmount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) }})
            </button>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Valor (R$) *</label>
            <input v-model="form.amount" type="number" step="0.01" min="0" class="platform-input" required />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Data do pagamento</label>
            <input v-model="form.paid_at" type="date" class="platform-input" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Referência</label>
            <input v-model="form.reference" placeholder="PIX, boleto, transferência..." class="platform-input" />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Observações</label>
            <textarea v-model="form.notes" rows="3" class="platform-input" />
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="platform-btn-primary" :disabled="form.processing">
                {{ form.processing ? 'Salvando...' : 'Registrar pagamento' }}
            </button>
            <Link :href="route('platform.payments.index')" class="platform-btn-secondary">
                Cancelar
            </Link>
        </div>
    </form>
</template>
