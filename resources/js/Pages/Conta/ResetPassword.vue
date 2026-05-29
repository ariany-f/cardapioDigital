<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({ token: String, email: String, globalAccount: Boolean });

const page = usePage();
const tenant = computed(() => page.props.tenant);

const form = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
});

const submitRoute = computed(() =>
    props.globalAccount
        ? route('app.conta.password.store')
        : route('tenant.conta.password.store', { tenant: tenant.value.slug }),
);

const submit = () => form.post(submitRoute.value);
</script>

<template>
    <Head title="Nova senha" />
    <div class="mx-auto max-w-md rounded-3xl border bg-white p-8 shadow-sm">
        <h1 class="text-xl font-bold">Definir nova senha</h1>
        <form class="mt-4 space-y-3" @submit.prevent="submit">
            <input v-model="form.email" type="email" class="w-full rounded-xl border px-3 py-2" required />
            <input v-model="form.password" type="password" class="w-full rounded-xl border px-3 py-2" placeholder="Nova senha" required />
            <input v-model="form.password_confirmation" type="password" class="w-full rounded-xl border px-3 py-2" placeholder="Confirmar" required />
            <button type="submit" class="w-full rounded-xl bg-orange-600 py-2.5 font-semibold text-white" :disabled="form.processing">
                Salvar senha
            </button>
        </form>
    </div>
</template>
