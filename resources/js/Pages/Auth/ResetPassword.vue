<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthLayout });

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Nova senha" />

    <h2 class="text-2xl font-bold text-stone-900">Definir nova senha</h2>

    <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-stone-700">E-mail</label>
            <input id="email" v-model="form.email" type="email" class="admin-input" required autofocus />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium text-stone-700">Nova senha</label>
            <input id="password" v-model="form.password" type="password" class="admin-input" required />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium text-stone-700">Confirmar senha</label>
            <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="admin-input" required />
            <p v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-600">{{ form.errors.password_confirmation }}</p>
        </div>
        <button type="submit" class="admin-btn-primary w-full" :disabled="form.processing">Salvar senha</button>
    </form>
</template>
