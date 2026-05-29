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
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const registerRoute = computed(() =>
    props.globalAccount
        ? route('app.conta.register.store')
        : route('tenant.conta.register.store', { tenant: tenant.value.slug }),
);

const loginRoute = computed(() =>
    props.globalAccount
        ? route('app.conta.login')
        : route('tenant.conta.login', { tenant: tenant.value.slug }),
);

const submit = () => {
    form.post(registerRoute.value, {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Criar conta" />

    <div class="mx-auto max-w-md">
        <div class="rounded-3xl border border-stone-200 bg-white p-8 shadow-sm">
            <h1 class="text-2xl font-bold text-stone-900">Criar conta</h1>
            <p v-if="globalAccount" class="mt-1 text-sm text-stone-600">
                Uma conta para pedir em qualquer restaurante da plataforma.
            </p>
            <p v-else class="mt-1 text-sm text-stone-600">
                Cadastre-se uma vez e use em todos os restaurantes.
            </p>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Nome</label>
                    <input v-model="form.name" type="text" required class="menu-input" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">E-mail</label>
                    <input v-model="form.email" type="email" required class="menu-input" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Telefone</label>
                    <input v-model="form.phone" type="tel" required class="menu-input" />
                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Senha</label>
                    <input v-model="form.password" type="password" required class="menu-input" minlength="6" />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Confirmar senha</label>
                    <input v-model="form.password_confirmation" type="password" required class="menu-input" />
                </div>
                <button type="submit" class="menu-btn-primary w-full" :disabled="form.processing">
                    Cadastrar
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-stone-600">
                Já tem conta?
                <Link :href="loginRoute" class="font-semibold text-orange-600 hover:text-orange-700">
                    Entrar
                </Link>
            </p>
        </div>
    </div>
</template>
