<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthLayout });

defineProps({ status: { type: String } });

const form = useForm({ email: '' });

const submit = () => form.post(route('password.email'));
</script>

<template>
    <Head title="Recuperar senha" />

    <h2 class="text-2xl font-bold text-stone-900">Recuperar senha</h2>
    <p class="mt-1 text-sm text-stone-600">
        Informe seu e-mail e enviaremos um link para definir uma nova senha.
    </p>

    <div v-if="status" class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-800">
        {{ status }}
    </div>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-stone-700">E-mail</label>
            <input id="email" v-model="form.email" type="email" class="admin-input" required autofocus />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <Link :href="route('login')" class="text-sm text-stone-600 hover:text-orange-600">← Voltar ao login</Link>
            <button type="submit" class="admin-btn-primary px-6" :disabled="form.processing">Enviar link</button>
        </div>
    </form>
</template>
