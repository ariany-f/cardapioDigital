<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    globalAccount: { type: Boolean, default: false },
});

const page = usePage();
const tenant = computed(() => page.props.tenant);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const loginRoute = computed(() =>
    props.globalAccount
        ? route('app.conta.login.store')
        : route('tenant.conta.login.store', { tenant: tenant.value.slug }),
);

const registerRoute = computed(() =>
    props.globalAccount
        ? route('app.conta.register')
        : route('tenant.conta.register', { tenant: tenant.value.slug }),
);

const submit = () => {
    form.post(loginRoute.value, {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Entrar na conta" />

    <div class="mx-auto max-w-md">
        <div class="rounded-3xl border border-stone-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-bold text-stone-900">Minha conta</h1>
            <p v-if="globalAccount" class="mt-1 text-sm text-stone-600">
                Entre para ver seus pedidos em todos os restaurantes.
            </p>
            <p v-else class="mt-1 text-sm text-stone-600">
                Entre para ver seus pedidos. A mesma conta vale em qualquer restaurante da plataforma.
            </p>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">E-mail</label>
                    <input v-model="form.email" type="email" required class="menu-input" autocomplete="email" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between">
                        <label class="text-sm font-medium text-stone-700">Senha</label>
                        <Link
                            :href="globalAccount ? route('app.conta.password.request') : route('tenant.conta.password.request', { tenant: tenant.slug })"
                            class="text-xs text-orange-600 hover:underline"
                        >
                            Esqueci a senha
                        </Link>
                    </div>
                    <input v-model="form.password" type="password" required class="menu-input" autocomplete="current-password" />
                </div>
                <label class="flex items-center gap-2 text-sm text-stone-600">
                    <input v-model="form.remember" type="checkbox" class="rounded border-stone-300" />
                    Lembrar de mim
                </label>
                <button type="submit" class="menu-btn-primary w-full" :disabled="form.processing">
                    Entrar
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-stone-600">
                Ainda não tem conta?
                <Link :href="registerRoute" class="font-semibold text-orange-600 hover:text-orange-700">
                    Cadastre-se
                </Link>
            </p>
            <p v-if="tenant" class="mt-3 text-center">
                <Link :href="route('tenant.home', { tenant: tenant.slug })" class="text-sm text-stone-500 hover:text-stone-700">
                    Voltar ao cardápio
                </Link>
            </p>
        </div>
    </div>
</template>
