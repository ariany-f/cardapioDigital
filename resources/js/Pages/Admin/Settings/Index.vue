<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    settings: Object,
    deliverySettings: Object,
    paymentSettings: Object,
    motoboys_enabled: { type: Boolean, default: true },
    orderSettings: { type: Object, default: () => ({}) },
});

const page = usePage();
const tenant = page.props.tenant;

const form = useForm({
    guest_checkout_enabled: props.orderSettings?.guest_checkout_enabled ?? true,
    motoboy_auto_accept_assignments: props.deliverySettings?.motoboy_auto_accept_assignments ?? false,
    pix_enabled: props.paymentSettings?.pix_enabled ?? true,
    pix_key_type: props.paymentSettings?.pix_key_type ?? 'phone',
    pix_key: props.paymentSettings?.pix_key ?? '',
    pix_beneficiary: props.paymentSettings?.pix_beneficiary ?? '',
    card_online_enabled: props.paymentSettings?.card_online_enabled ?? false,
    card_online_instructions: props.paymentSettings?.card_online_instructions ?? '',
    name: props.settings.name,
    public_description: props.settings.public_description ?? '',
    phone: props.settings.phone ?? '',
    whatsapp: props.settings.whatsapp ?? '',
    website: props.settings.website ?? '',
    instagram: props.settings.instagram ?? '',
    theme_primary_color: props.settings.theme_primary_color,
    theme_secondary_color: props.settings.theme_secondary_color,
    logo: null,
});

const submit = () =>
    form
        .transform((data) => ({ ...data, _method: 'put' }))
        .post(route('tenant.admin.settings.update', { tenant: tenant.slug }), {
            forceFormData: true,
        });
</script>

<template>
    <Head title="Configurações" />

    <h1 class="admin-page-title">Aparência e página pública</h1>
    <p class="mt-1 text-sm text-stone-500">Logo, cores e informações exibidas na home do restaurante.</p>

    <form class="admin-card mt-8 max-w-2xl space-y-4" @submit.prevent="submit">
        <div>
            <label class="mb-1 block text-sm font-medium text-stone-700">Nome do restaurante</label>
            <input v-model="form.name" class="admin-input" required />
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-stone-700">Descrição pública</label>
            <textarea v-model="form.public_description" class="admin-input" rows="3" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Telefone</label>
                <input v-model="form.phone" class="admin-input" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">WhatsApp</label>
                <input v-model="form.whatsapp" class="admin-input" placeholder="5511999999999" />
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Site</label>
                <input v-model="form.website" type="url" class="admin-input" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Instagram</label>
                <input v-model="form.instagram" class="admin-input" placeholder="@restaurante" />
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Cor primária</label>
                <input v-model="form.theme_primary_color" type="color" class="h-10 w-full rounded-xl border border-stone-200" />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Cor secundária</label>
                <input v-model="form.theme_secondary_color" type="color" class="h-10 w-full rounded-xl border border-stone-200" />
            </div>
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-stone-700">Logo</label>
            <img v-if="settings.logo_url && !form.logo" :src="settings.logo_url" alt="Logo" class="mb-2 h-16 w-16 rounded-xl object-cover" />
            <input type="file" accept="image/*" class="text-sm" @change="form.logo = $event.target.files[0]" />
        </div>
        <div class="rounded-xl border border-stone-200 bg-stone-50 p-4">
            <h2 class="font-semibold text-stone-900">Cardápio público</h2>
            <p class="mt-1 text-xs text-stone-500">
                Controle quem pode finalizar pedidos pelo link do cardápio (sem estar logado).
            </p>
            <label class="mt-3 flex cursor-pointer items-start gap-2 text-sm text-stone-700">
                <input v-model="form.guest_checkout_enabled" type="checkbox" class="mt-0.5" />
                <span>
                    <strong>Visitantes podem comprar</strong> — permite pedir informando nome e WhatsApp, sem criar
                    conta. Desligado: só clientes com login (ou pedido por QR da mesa).
                </span>
            </label>
        </div>
        <div class="rounded-xl border border-stone-200 bg-stone-50 p-4">
            <h2 class="font-semibold text-stone-900">Pagamentos online (sem gateway)</h2>
            <p class="mt-1 text-xs text-stone-500">PIX estático (chave copia e cola) e cartão com confirmação manual no admin.</p>
            <label class="mt-3 flex items-center gap-2 text-sm">
                <input v-model="form.pix_enabled" type="checkbox" /> Aceitar PIX no checkout
            </label>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <input v-model="form.pix_key" class="admin-input" placeholder="Chave PIX" />
                <input v-model="form.pix_beneficiary" class="admin-input" placeholder="Nome do beneficiário" />
            </div>
            <label class="mt-3 flex items-center gap-2 text-sm">
                <input v-model="form.card_online_enabled" type="checkbox" /> Aceitar cartão online (manual)
            </label>
            <textarea v-model="form.card_online_instructions" class="admin-input mt-2" rows="2" placeholder="Instruções para o cliente (ex.: link de pagamento)" />
        </div>
        <div v-if="motoboys_enabled" class="rounded-xl border border-stone-200 bg-stone-50 p-4">
            <h2 class="font-semibold text-stone-900">Entregadores</h2>
            <label class="mt-3 flex cursor-pointer items-start gap-2 text-sm text-stone-700">
                <input v-model="form.motoboy_auto_accept_assignments" type="checkbox" class="mt-0.5" />
                <span>
                    <strong>Aceite automático</strong> — vale só para entregadores com painel web. Ao atribuir, o pedido vai direto
                    para o app deles, sem precisar aceitar manualmente. Entregadores “só impresso” não usam o app.
                </span>
            </label>
        </div>
        <button type="submit" class="admin-btn-primary" :disabled="form.processing">Salvar configurações</button>
    </form>
</template>
