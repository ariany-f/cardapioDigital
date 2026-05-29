<script setup>
import CommunicationNotice from '@/Components/Public/CommunicationNotice.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({ types: Array });

const page = usePage();
const tenant = computed(() => page.props.tenant);
const customer = computed(() => page.props.auth?.customer);

const form = useForm({
    type: 'help',
    subject: '',
    message: '',
    order_number: '',
    guest_name: customer.value?.name ?? '',
    guest_phone: customer.value?.phone ?? '',
    guest_email: customer.value?.email ?? '',
});

const submit = () => form.post(route('tenant.support.store', { tenant: tenant.value.slug }));
</script>

<template>
    <Head title="Ajuda e suporte" />

    <div class="mx-auto max-w-lg">
        <h1 class="text-2xl font-bold text-stone-900">Falar com o restaurante</h1>
        <CommunicationNotice class="mt-4" />
        <p class="mt-3 text-sm text-stone-600">
            {{ page.props.communication_disclaimer?.customer?.support_hint }}
            Não há reembolso automático pelo sistema — combine diretamente com o estabelecimento.
        </p>

        <form class="mt-6 space-y-4 rounded-3xl border border-stone-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Tipo</label>
                <select v-model="form.type" class="menu-input">
                    <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Assunto</label>
                <input v-model="form.subject" class="menu-input" required />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Mensagem</label>
                <textarea v-model="form.message" class="menu-input" rows="4" required />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-stone-700">Nº do pedido (opcional)</label>
                <input v-model="form.order_number" class="menu-input" placeholder="Ex: ACME-0001" />
            </div>
            <template v-if="!customer">
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Seu nome</label>
                    <input v-model="form.guest_name" class="menu-input" required />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Telefone</label>
                    <input v-model="form.guest_phone" class="menu-input" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">E-mail</label>
                    <input v-model="form.guest_email" type="email" class="menu-input" />
                </div>
            </template>
            <button type="submit" class="menu-btn-primary w-full" :disabled="form.processing">Enviar mensagem</button>
        </form>
    </div>
</template>
