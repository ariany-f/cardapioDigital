<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthLayout });

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Cadastro" />

    <h2 class="text-2xl font-bold text-stone-900">Criar conta</h2>
    <p class="mt-1 text-sm text-stone-600">
        Cadastro de usuário do painel. Prefira que o administrador crie seu acesso.
    </p>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-stone-700">Nome</label>
            <input id="name" v-model="form.name" type="text" class="admin-input" required autofocus />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-stone-700">E-mail</label>
            <input id="email" v-model="form.email" type="email" class="admin-input" required />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-stone-700">Senha</label>
            <input id="password" v-model="form.password" type="password" class="admin-input" required />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-stone-700">Confirmar senha</label>
            <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="admin-input" required />
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <Link :href="route('login')" class="text-sm text-stone-600 hover:text-orange-600">Já tenho conta</Link>
            <button type="submit" class="admin-btn-primary px-6" :disabled="form.processing">Cadastrar</button>
        </div>
    </form>
</template>
