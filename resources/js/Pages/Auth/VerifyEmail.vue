<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthLayout });

const props = defineProps({ status: { type: String } });

const form = useForm({});

const submit = () => form.post(route('verification.send'));

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Verificar e-mail" />

    <h2 class="text-2xl font-bold text-stone-900">Verificar e-mail</h2>
    <p class="mt-1 text-sm text-stone-600">
        Confirme seu e-mail pelo link que enviamos. Não recebeu? Podemos reenviar.
    </p>

    <p v-if="verificationLinkSent" class="mt-4 text-sm text-green-700">
        Um novo link foi enviado para o seu e-mail.
    </p>

    <form class="mt-6 flex flex-wrap items-center justify-between gap-3" @submit.prevent="submit">
        <button type="submit" class="admin-btn-primary" :disabled="form.processing">Reenviar e-mail</button>
        <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="text-sm text-stone-600 hover:text-orange-600"
        >
            Sair
        </Link>
    </form>
</template>
