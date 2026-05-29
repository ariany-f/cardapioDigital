<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    payment: Object,
});

const form = useForm({
    amount: props.payment.amount ?? '',
    reference: props.payment.reference ?? '',
    notes: props.payment.notes ?? '',
    paid_at: props.payment.paid_at ?? '',
});

const submit = () => form.put(route('platform.payments.update', props.payment.id));
</script>

<template>
    <Head :title="`Editar pagamento — ${payment.tenant?.name}`" />

    <Link
        :href="route('platform.payments.index', { tenant_id: payment.tenant_id })"
        class="text-sm text-slate-500 hover:text-slate-700"
    >
        ← Pagamentos
    </Link>

    <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900">Editar pagamento</h1>
    <p class="mt-1 text-sm text-slate-500">{{ payment.tenant?.name }} · /{{ payment.tenant?.slug }}</p>

    <form class="platform-card mt-6 max-w-lg space-y-4" @submit.prevent="submit">
        <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-600">
            <p>
                Plano na época:
                <strong>{{ payment.subscription?.plan?.name ?? '—' }}</strong>
            </p>
            <p v-if="payment.marked_by" class="mt-1">
                Registrado por: <strong>{{ payment.marked_by.name }}</strong>
            </p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Valor (R$) *</label>
            <input v-model="form.amount" type="number" step="0.01" min="0" class="platform-input" required />
            <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Data do pagamento</label>
            <input v-model="form.paid_at" type="date" class="platform-input" />
            <p v-if="form.errors.paid_at" class="mt-1 text-xs text-red-600">{{ form.errors.paid_at }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Referência</label>
            <input v-model="form.reference" placeholder="PIX, boleto, transferência..." class="platform-input" />
            <p v-if="form.errors.reference" class="mt-1 text-xs text-red-600">{{ form.errors.reference }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Observações</label>
            <textarea v-model="form.notes" rows="3" class="platform-input" />
            <p v-if="form.errors.notes" class="mt-1 text-xs text-red-600">{{ form.errors.notes }}</p>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="platform-btn-primary" :disabled="form.processing">
                {{ form.processing ? 'Salvando...' : 'Salvar alterações' }}
            </button>
            <Link
                :href="route('platform.payments.index', { tenant_id: payment.tenant_id })"
                class="platform-btn-secondary"
            >
                Cancelar
            </Link>
        </div>
    </form>
</template>
