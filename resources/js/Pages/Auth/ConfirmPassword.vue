<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthLayout });

const form = useForm({ password: '' });

const submit = () => form.post(route('password.confirm'), { onFinish: () => form.reset() });
</script>

<template>
    <Head title="Confirmar senha" />

    <h2 class="text-2xl font-bold text-stone-900">Confirmar senha</h2>
    <p class="mt-1 text-sm text-stone-600">Por segurança, confirme sua senha para continuar.</p>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-stone-700">Senha</label>
            <input id="password" v-model="form.password" type="password" class="admin-input" required autofocus />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
        </div>
        <button type="submit" class="admin-btn-primary w-full" :disabled="form.processing">Confirmar</button>
    </form>
</template>
