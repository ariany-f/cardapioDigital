<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    lead: Object,
    statusLabels: Object,
});

const form = useForm({
    status: props.lead.status,
    internal_notes: props.lead.internal_notes ?? '',
});

const submit = () => form.put(route('platform.marketing-leads.update', props.lead.id));

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

const mailto = () => {
    const subject = encodeURIComponent(`App Cardápio — ${props.lead.restaurant_name}`);
    const body = encodeURIComponent(
        `Olá ${props.lead.contact_name},\n\nRecebemos sua solicitação para o restaurante ${props.lead.restaurant_name}.\n\n`,
    );
    return `mailto:${props.lead.email}?subject=${subject}&body=${body}`;
};

const whatsapp = () => {
    const digits = (props.lead.phone || '').replace(/\D/g, '');
    if (!digits) return null;
    const text = encodeURIComponent(
        `Olá ${props.lead.contact_name}! Recebemos sua solicitação do ${props.lead.restaurant_name} no App Cardápio.`,
    );
    return `https://wa.me/55${digits}?text=${text}`;
};
</script>

<template>
    <Head :title="`${lead.restaurant_name} — Solicitação`" />

    <div class="mb-4">
        <Link :href="route('platform.marketing-leads.index')" class="text-sm text-indigo-600 hover:underline">
            ← Voltar à lista
        </Link>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ lead.restaurant_name }}</h1>
            <p class="mt-1 text-sm text-slate-500">Enviada em {{ formatDate(lead.created_at) }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a :href="mailto()" class="platform-btn-secondary">E-mail</a>
            <a
                v-if="whatsapp()"
                :href="whatsapp()"
                target="_blank"
                rel="noopener noreferrer"
                class="platform-btn-secondary"
            >
                WhatsApp
            </a>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="platform-card space-y-4">
            <h2 class="font-semibold text-slate-900">Dados do solicitante</h2>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Nome do contato</dt>
                    <dd class="font-medium text-slate-900">{{ lead.contact_name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">E-mail</dt>
                    <dd>
                        <a :href="`mailto:${lead.email}`" class="text-indigo-600 hover:underline">{{ lead.email }}</a>
                    </dd>
                </div>
                <div v-if="lead.phone">
                    <dt class="text-slate-500">Telefone</dt>
                    <dd class="font-medium text-slate-900">{{ lead.phone }}</dd>
                </div>
                <div v-if="lead.city">
                    <dt class="text-slate-500">Cidade</dt>
                    <dd class="font-medium text-slate-900">{{ lead.city }}</dd>
                </div>
                <div v-if="lead.contacted_at">
                    <dt class="text-slate-500">Primeiro contato</dt>
                    <dd class="font-medium text-slate-900">{{ formatDate(lead.contacted_at) }}</dd>
                </div>
            </dl>
            <div v-if="lead.message" class="border-t border-slate-100 pt-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Mensagem</p>
                <p class="mt-2 whitespace-pre-wrap text-sm text-slate-800">{{ lead.message }}</p>
            </div>
        </div>

        <form class="platform-card space-y-4" @submit.prevent="submit">
            <h2 class="font-semibold text-slate-900">Acompanhamento interno</h2>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
                <select v-model="form.status" class="platform-input">
                    <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                </select>
                <p v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</p>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Observações internas</label>
                <textarea
                    v-model="form.internal_notes"
                    class="platform-input min-h-[120px]"
                    placeholder="Ligação feita, proposta enviada, etc."
                />
                <p v-if="form.errors.internal_notes" class="mt-1 text-xs text-red-600">{{ form.errors.internal_notes }}</p>
            </div>
            <button type="submit" class="platform-btn-primary" :disabled="form.processing">Salvar</button>
        </form>
    </div>
</template>
