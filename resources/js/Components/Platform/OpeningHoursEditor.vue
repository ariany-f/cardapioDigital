<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['update:modelValue']);

const days = [
    { key: 'mon', label: 'Segunda' },
    { key: 'tue', label: 'Terça' },
    { key: 'wed', label: 'Quarta' },
    { key: 'thu', label: 'Quinta' },
    { key: 'fri', label: 'Sexta' },
    { key: 'sat', label: 'Sábado' },
    { key: 'sun', label: 'Domingo' },
];

const rows = computed(() =>
    days.map((day) => {
        const range = props.modelValue?.[day.key];
        return {
            ...day,
            enabled: Array.isArray(range) && range.length === 2,
            open: range?.[0] ?? '09:00',
            close: range?.[1] ?? '22:00',
        };
    }),
);

const updateDay = (key, enabled, open, close) => {
    const next = { ...props.modelValue };
    if (enabled) {
        next[key] = [open || '09:00', close || '22:00'];
    } else {
        delete next[key];
    }
    emit('update:modelValue', next);
};
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="row in rows"
            :key="row.key"
            class="grid grid-cols-[auto_1fr_1fr] items-center gap-2 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-sm"
        >
            <label class="flex min-w-[5.5rem] items-center gap-2">
                <input
                    type="checkbox"
                    :checked="row.enabled"
                    @change="updateDay(row.key, $event.target.checked, row.open, row.close)"
                />
                <span>{{ row.label }}</span>
            </label>
            <input
                type="time"
                :value="row.open"
                class="rounded border px-2 py-1"
                :disabled="!row.enabled"
                @input="updateDay(row.key, true, $event.target.value, row.close)"
            />
            <input
                type="time"
                :value="row.close"
                class="rounded border px-2 py-1"
                :disabled="!row.enabled"
                @input="updateDay(row.key, true, row.open, $event.target.value)"
            />
        </div>
    </div>
</template>
