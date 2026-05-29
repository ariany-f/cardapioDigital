<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthLayout });

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Entrar" />

    <h2 class="text-2xl font-bold text-stone-900">Entrar</h2>
    <p class="mt-1 text-sm text-stone-600">Admin do restaurante ou superadmin da plataforma.</p>

    <div v-if="status" class="mt-4 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-800">
        {{ status }}
    </div>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-stone-700">E-mail</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                class="admin-input"
                required
                autofocus
                autocomplete="username"
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-stone-700">Senha</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                class="admin-input"
                required
                autocomplete="current-password"
            />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-stone-600">
            <input v-model="form.remember" type="checkbox" class="rounded border-stone-300" />
            Lembrar de mim
        </label>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <Link
                v-if="canResetPassword"
                :href="route('password.request')"
                class="text-sm text-stone-600 hover:text-orange-600"
            >
                Esqueci a senha
            </Link>
            <button type="submit" class="admin-btn-primary px-6" :disabled="form.processing">
                {{ form.processing ? 'Entrando...' : 'Entrar' }}
            </button>
        </div>
    </form>
</template>
