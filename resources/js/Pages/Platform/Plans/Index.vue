<script setup>
import PlanFeatureFields from '@/Components/Platform/PlanFeatureFields.vue';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { defaultPlanFeatures, usePlanFeatures } from '@/composables/usePlanFeatures';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: PlatformLayout });

defineProps({ plans: Array });

const { t, listFeatures } = usePlanFeatures();
const creating = ref(false);
const editingId = ref(null);

const createForm = useForm({
    name: '',
    slug: '',
    price_monthly: 0,
    is_active: true,
    features_json: defaultPlanFeatures(),
});

const editForm = useForm({
    name: '',
    price_monthly: 0,
    is_active: true,
    features_json: defaultPlanFeatures(),
});

const startCreate = () => {
    editingId.value = null;
    creating.value = true;
    createForm.reset();
    createForm.features_json = defaultPlanFeatures();
};

const cancelCreate = () => {
    creating.value = false;
    createForm.reset();
};

const submitCreate = () =>
    createForm.post(route('platform.plans.store'), {
        onSuccess: () => {
            creating.value = false;
            createForm.reset();
        },
    });

const startEdit = (plan) => {
    creating.value = false;
    editingId.value = plan.id;
    editForm.name = plan.name;
    editForm.price_monthly = plan.price_monthly;
    editForm.is_active = plan.is_active;
    editForm.features_json = { ...defaultPlanFeatures(), ...(plan.features_json || {}) };
    editForm.clearErrors();
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
};

const submitEdit = (plan) =>
    editForm.put(route('platform.plans.update', plan.id), {
        onSuccess: () => {
            editingId.value = null;
            editForm.reset();
        },
    });

const formatPrice = (value) =>
    parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
</script>

<template>
    <Head :title="t('page_title')" />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ t('page_title') }}</h1>
            <p class="mt-1 text-sm text-stone-500">{{ t('intro') }}</p>
        </div>
        <button
            v-if="!creating"
            type="button"
            class="platform-btn-primary"
            @click="startCreate"
        >
            {{ t('create') }}
        </button>
    </div>

    <form v-if="creating" class="platform-card mt-6 max-w-lg space-y-4" @submit.prevent="submitCreate">
        <h2 class="text-base font-semibold text-slate-900">{{ t('create_title') }}</h2>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">{{ t('name') }} *</label>
            <input v-model="createForm.name" type="text" required class="platform-input" />
            <p v-if="createForm.errors.name" class="mt-1 text-sm text-red-600">{{ createForm.errors.name }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">{{ t('slug_field') }}</label>
            <input v-model="createForm.slug" type="text" class="platform-input" :placeholder="t('slug_placeholder')" />
            <p class="mt-1 text-xs text-slate-500">{{ t('slug_help') }}</p>
            <p v-if="createForm.errors.slug" class="mt-1 text-sm text-red-600">{{ createForm.errors.slug }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">{{ t('price') }} *</label>
            <input v-model="createForm.price_monthly" type="number" step="0.01" min="0" required class="platform-input" />
            <p v-if="createForm.errors.price_monthly" class="mt-1 text-sm text-red-600">{{ createForm.errors.price_monthly }}</p>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="createForm.is_active" type="checkbox" class="rounded border-slate-300" />
            {{ t('active') }}
        </label>

        <PlanFeatureFields v-model:features-json="createForm.features_json" />

        <div class="flex gap-3 border-t border-slate-100 pt-4">
            <button type="button" class="platform-btn-secondary" @click="cancelCreate">
                {{ t('cancel') }}
            </button>
            <button type="submit" class="platform-btn-primary" :disabled="createForm.processing">
                {{ createForm.processing ? t('saving') : t('create_submit') }}
            </button>
        </div>
    </form>

    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article v-for="plan in plans" :key="plan.id" class="platform-card">
            <h2 class="text-lg font-semibold">{{ plan.name }}</h2>
            <p class="text-sm text-stone-500">{{ t('slug') }}: {{ plan.slug }}</p>
            <p class="mt-2 text-2xl font-bold">
                {{ formatPrice(plan.price_monthly) }}<span class="text-base font-normal text-stone-500">{{ t('per_month') }}</span>
            </p>
            <p class="mt-1 text-xs" :class="plan.is_active ? 'text-green-700' : 'text-slate-400'">
                {{ plan.is_active ? t('active') : t('inactive') }}
            </p>

            <ul class="mt-3 space-y-1.5 text-sm text-stone-700">
                <li v-for="feature in listFeatures(plan.features_json)" :key="feature.key" class="flex justify-between gap-2">
                    <span class="text-stone-600">{{ feature.label }}</span>
                    <span class="font-medium text-stone-900">{{ feature.value }}</span>
                </li>
            </ul>

            <button
                v-if="editingId !== plan.id"
                type="button"
                class="platform-btn-secondary mt-4 text-sm"
                @click="startEdit(plan)"
            >
                {{ t('edit') }}
            </button>

            <form v-else class="mt-4 space-y-3 border-t border-slate-100 pt-4" @submit.prevent="submitEdit(plan)">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ t('name') }}</label>
                    <input v-model="editForm.name" type="text" required class="platform-input" />
                    <p v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">{{ editForm.errors.name }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ t('price') }}</label>
                    <input v-model="editForm.price_monthly" type="number" step="0.01" min="0" required class="platform-input" />
                    <p v-if="editForm.errors.price_monthly" class="mt-1 text-sm text-red-600">{{ editForm.errors.price_monthly }}</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input v-model="editForm.is_active" type="checkbox" class="rounded border-slate-300" />
                    {{ t('active') }}
                </label>

                <PlanFeatureFields v-model:features-json="editForm.features_json" />

                <div class="flex gap-2">
                    <button type="button" class="platform-btn-secondary flex-1" @click="cancelEdit">
                        {{ t('cancel') }}
                    </button>
                    <button type="submit" class="platform-btn-primary flex-1" :disabled="editForm.processing">
                        {{ editForm.processing ? t('saving') : t('save') }}
                    </button>
                </div>
            </form>
        </article>
    </div>
</template>
