<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    motoboy: { type: Object, required: true },
    tenantSlug: { type: String, required: true },
});

const emit = defineEmits(['close', 'saved']);

const loginForm = useForm({
    uses_app: props.motoboy.uses_app ?? true,
    email: props.motoboy.email ?? '',
    password: '',
    is_active: props.motoboy.is_active ?? true,
});

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const saveLogin = () => {
    loginForm.post(route('tenant.admin.motoboys.login.update', { tenant: props.tenantSlug, motoboy: props.motoboy.id }), {
        preserveScroll: true,
        onSuccess: () => {
            loginForm.password = '';
            emit('saved');
        },
    });
};

const resetPassword = () => {
    passwordForm.post(route('tenant.admin.motoboys.reset-password', { tenant: props.tenantSlug, motoboy: props.motoboy.id }), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            emit('saved');
        },
    });
};
</script>

<template>
    <div class="mt-4 rounded-xl border border-violet-200 bg-violet-50/80 p-4">
        <div class="flex items-start justify-between gap-2">
            <div>
                <h3 class="font-semibold text-violet-950">Acesso ao painel web</h3>
                <p class="mt-0.5 text-xs text-violet-800/80">{{ motoboy.name }}</p>
            </div>
            <button type="button" class="text-sm text-violet-700 hover:text-violet-900" @click="emit('close')">Fechar</button>
        </div>

        <form class="mt-4 space-y-3" @submit.prevent="saveLogin">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-stone-800">
                <input v-model="loginForm.is_active" type="checkbox" class="rounded border-stone-300" />
                Cadastro ativo (pode ser atribuído a pedidos)
            </label>

            <div class="flex flex-col gap-2 sm:flex-row sm:gap-4">
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <input v-model="loginForm.uses_app" type="radio" :value="true" />
                    Usa o painel web
                </label>
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <input v-model="loginForm.uses_app" type="radio" :value="false" />
                    Só comanda impressa
                </label>
            </div>

            <template v-if="loginForm.uses_app">
                <div>
                    <label class="mb-1 block text-xs font-medium text-stone-600">E-mail de login</label>
                    <input v-model="loginForm.email" type="email" class="admin-input" required />
                    <p v-if="loginForm.errors.email" class="mt-1 text-xs text-red-600">{{ loginForm.errors.email }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-stone-600">
                        {{ motoboy.has_app_login ? 'Nova senha (opcional)' : 'Senha inicial' }}
                    </label>
                    <input
                        v-model="loginForm.password"
                        type="password"
                        class="admin-input"
                        :placeholder="motoboy.has_app_login ? 'Deixe vazio para manter a atual' : 'Mínimo 6 caracteres'"
                        :required="!motoboy.has_app_login"
                    />
                    <p v-if="loginForm.errors.password" class="mt-1 text-xs text-red-600">{{ loginForm.errors.password }}</p>
                </div>
            </template>

            <button type="submit" class="admin-btn-primary text-sm" :disabled="loginForm.processing">
                Salvar acesso
            </button>
        </form>

        <form
            v-if="loginForm.uses_app && motoboy.has_app_login"
            class="mt-4 border-t border-violet-200 pt-4"
            @submit.prevent="resetPassword"
        >
            <p class="text-xs font-medium text-stone-600">Redefinir senha</p>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                <input
                    v-model="passwordForm.password"
                    type="password"
                    class="admin-input"
                    placeholder="Nova senha"
                    required
                />
                <input
                    v-model="passwordForm.password_confirmation"
                    type="password"
                    class="admin-input"
                    placeholder="Confirmar senha"
                    required
                />
            </div>
            <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-red-600">{{ passwordForm.errors.password }}</p>
            <button type="submit" class="mt-2 text-sm font-medium text-violet-800 underline hover:text-violet-950" :disabled="passwordForm.processing">
                Redefinir senha
            </button>
        </form>
    </div>
</template>
