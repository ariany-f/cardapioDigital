<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({ globalAccount: Boolean });

const page = usePage();
const tenant = computed(() => page.props.tenant);
const form = useForm({ email: '' });

const submitRoute = computed(() =>
    props.globalAccount
        ? route('app.conta.password.email')
        : route('tenant.conta.password.email', { tenant: tenant.value.slug }),
);

const loginRoute = computed(() =>
    props.globalAccount ? route('app.conta.login') : route('tenant.conta.login', { tenant: tenant.value.slug }),
);

const submit = () => form.post(submitRoute.value);
</script>

<template>
    <Head title="Recuperar senha" />
    <div class="mx-auto max-w-md rounded-3xl border bg-white p-8 shadow-sm">
        <h1 class="text-xl font-bold">Esqueci minha senha</h1>
        <form class="mt-4 space-y-3" @submit.prevent="submit">
            <input v-model="form.email" type="email" class="w-full rounded-xl border px-3 py-2" required />
            <button type="submit" class="w-full rounded-xl bg-orange-600 py-2.5 font-semibold text-white" :disabled="form.processing">
                Enviar link
            </button>
        </form>
        <Link :href="loginRoute" class="mt-4 block text-center text-sm text-orange-600">Voltar ao login</Link>
    </div>
</template>
