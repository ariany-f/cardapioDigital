<script setup>
import { computed } from 'vue';

const props = defineProps({
    role: { type: String, required: true },
    rolePermissions: { type: Object, required: true },
    roleLabels: { type: Object, required: true },
});

const detail = computed(() => props.rolePermissions?.[props.role] ?? null);
</script>

<template>
    <div
        v-if="detail"
        class="rounded-xl border border-orange-100 bg-orange-50/60 p-4 text-sm text-stone-700"
        role="region"
        :aria-label="`Permissões do perfil ${roleLabels[role] || role}`"
    >
        <p class="font-semibold text-stone-900">
            O que {{ roleLabels[role] || role }} pode acessar
        </p>
        <p class="mt-1 text-stone-600">{{ detail.summary }}</p>

        <div v-if="detail.areas?.length" class="mt-3">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Menu do painel</p>
            <ul class="mt-1.5 flex flex-wrap gap-1.5">
                <li
                    v-for="area in detail.areas"
                    :key="area"
                    class="rounded-full bg-white px-2.5 py-0.5 text-xs font-medium text-stone-800 ring-1 ring-stone-200"
                >
                    {{ area }}
                </li>
            </ul>
        </div>

        <div v-if="detail.actions?.length" class="mt-3">
            <p class="text-xs font-medium uppercase tracking-wide text-stone-500">Ações permitidas</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5 text-stone-600">
                <li v-for="action in detail.actions" :key="action">{{ action }}</li>
            </ul>
        </div>

        <p v-if="detail.branch_note" class="mt-3 rounded-lg bg-white/80 px-3 py-2 text-xs text-stone-600 ring-1 ring-stone-200/80">
            <span class="font-medium text-stone-700">Filiais:</span>
            {{ detail.branch_note }}
        </p>
    </div>
</template>
