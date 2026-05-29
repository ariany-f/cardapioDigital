<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ tenant: Object });
const form = useForm({ email: '', password: '', remember: false });

const submit = () =>
    form.post(route('tenant.entregador.login.store', { tenant: props.tenant.slug }));
</script>

<template>
    <Head title="Entregador — Login" />
    <div class="flex min-h-screen items-center justify-center bg-stone-100 px-4">
        <form class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-sm" @submit.prevent="submit">
            <h1 class="text-xl font-bold text-stone-900">Painel do entregador</h1>
            <p class="mt-1 text-sm text-stone-500">{{ tenant?.name }} · acesso pelo navegador</p>
            <div class="mt-6 space-y-3">
                <input v-model="form.email" type="email" class="w-full rounded-xl border px-3 py-2" placeholder="E-mail" required />
                <input v-model="form.password" type="password" class="w-full rounded-xl border px-3 py-2" placeholder="Senha" required />
                <label class="flex items-center gap-2 text-sm"><input v-model="form.remember" type="checkbox" /> Lembrar</label>
                <button type="submit" class="w-full rounded-xl bg-brand py-2.5 font-semibold text-white" :disabled="form.processing">
                    Entrar
                </button>
            </div>
        </form>
    </div>
</template>
