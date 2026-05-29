<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { usePlanFeatures } from '@/composables/usePlanFeatures';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: PlatformLayout });

defineProps({ plans: Array });

const { t, listFeatures } = usePlanFeatures();
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

const formatPrice = (value) =>
    parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
</script>

<template>
    <Head :title="t('page_title')" />
    <h1 class="text-2xl font-bold text-slate-900">{{ t('page_title') }}</h1>
    <p class="mt-1 text-sm text-stone-500">{{ t('intro') }}</p>

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <article v-for="plan in plans" :key="plan.id" class="admin-card">
            <h2 class="text-lg font-semibold">{{ plan.name }}</h2>
            <p class="text-sm text-stone-500">{{ t('slug') }}: {{ plan.slug }}</p>
            <p class="mt-2 text-2xl font-bold">
                {{ formatPrice(plan.price_monthly) }}<span class="text-base font-normal text-stone-500">{{ t('per_month') }}</span>
            </p>
            <ul class="mt-3 space-y-1.5 text-sm text-stone-700">
                <li v-for="feature in listFeatures(plan.features_json)" :key="feature.key" class="flex justify-between gap-2">
                    <span class="text-stone-600">{{ feature.label }}</span>
                    <span class="font-medium text-stone-900">{{ feature.value }}</span>
                </li>
            </ul>
            <button type="button" class="admin-btn-secondary mt-4 text-sm" @click="startEdit(plan)">
                {{ t('edit') }}
            </button>

            <form v-if="editingId === plan.id" class="mt-4 space-y-2 border-t pt-4" @submit.prevent="submit(plan)">
                <input v-model="form.name" class="admin-input" />
                <input v-model="form.price_monthly" type="number" step="0.01" class="admin-input" />
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox" />
                    {{ t('active') }}
                </label>
                <button type="submit" class="admin-btn-primary w-full" :disabled="form.processing">
                    {{ t('save') }}
                </button>
            </form>
        </article>
    </div>
</template>
