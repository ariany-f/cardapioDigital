<script setup>
import { PLAN_BOOLEAN_FEATURE_KEYS, usePlanFeatures } from '@/composables/usePlanFeatures';

const featuresJson = defineModel('featuresJson', { type: Object, required: true });

const { t, featureLabel } = usePlanFeatures();
</script>

<template>
    <fieldset class="space-y-3 rounded-xl border border-slate-100 bg-slate-50/80 p-4">
        <legend class="px-1 text-sm font-semibold text-slate-800">{{ t('limits_title') }}</legend>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">{{ featureLabel('max_branches') }}</label>
            <input
                v-model.number="featuresJson.max_branches"
                type="number"
                min="1"
                max="999"
                required
                class="platform-input"
            />
        </div>

        <div class="space-y-2 border-t border-slate-200 pt-3">
            <label
                v-for="key in PLAN_BOOLEAN_FEATURE_KEYS"
                :key="key"
                class="flex cursor-pointer items-center gap-2 text-sm text-slate-700"
            >
                <input v-model="featuresJson[key]" type="checkbox" class="rounded border-slate-300" />
                {{ featureLabel(key) }}
            </label>
        </div>
    </fieldset>
</template>
