<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({ plans: Array });
const editingId = ref(null);

const form = useForm({
    name: '',
    price_monthly: 0,
    is_active: true,
    features_json: {},
});

const startEdit = (plan) => {
    editingId.value = plan.id;
    form.name = plan.name;
    form.price_monthly = plan.price_monthly;
    form.is_active = plan.is_active;
    form.features_json = { ...(plan.features_json || {}) };
};

const submit = (plan) =>
    form.put(route('platform.plans.update', plan.id), { onSuccess: () => (editingId.value = null) });
</script>

<template>
    <Head title="Planos" />
    <h1 class="text-2xl font-bold text-slate-900">Planos SaaS</h1>
    <p class="mt-1 text-sm text-stone-500">
        Recursos por plano (kds, pos, reports, webhooks, motoboys, max_branches). O módulo motoboys libera cadastro
        de entregadores do restaurante — não há frota da plataforma.
    </p>

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <article v-for="plan in plans" :key="plan.id" class="admin-card">
            <h2 class="text-lg font-semibold">{{ plan.name }}</h2>
            <p class="text-sm text-stone-500">slug: {{ plan.slug }}</p>
            <p class="mt-2 text-2xl font-bold">R$ {{ parseFloat(plan.price_monthly).toFixed(2) }}/mês</p>
            <ul class="mt-3 space-y-1 text-xs text-stone-600">
                <li v-for="(val, key) in plan.features_json" :key="key">{{ key }}: {{ val }}</li>
            </ul>
            <button type="button" class="admin-btn-secondary mt-4 text-sm" @click="startEdit(plan)">Editar</button>

            <form v-if="editingId === plan.id" class="mt-4 space-y-2 border-t pt-4" @submit.prevent="submit(plan)">
                <input v-model="form.name" class="admin-input" />
                <input v-model="form.price_monthly" type="number" step="0.01" class="admin-input" />
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox" /> Ativo
                </label>
                <button type="submit" class="admin-btn-primary w-full" :disabled="form.processing">Salvar</button>
            </form>
        </article>
    </div>
</template>
