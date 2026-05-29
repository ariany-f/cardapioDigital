<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    embedded: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const items = computed({
    get: () => (Array.isArray(props.modelValue) ? props.modelValue : []),
    set: (v) => emit('update:modelValue', v),
});

const addItem = () => {
    items.value = [
        ...items.value,
        {
            key: `item_${Date.now()}`,
            label: '',
            min_qty: 0,
            max_qty: 5,
            default_qty: 0,
        },
    ];
};

const removeItem = (index) => {
    items.value = items.value.filter((_, i) => i !== index);
};

const updateItem = (index, field, value) => {
    const next = [...items.value];
    next[index] = { ...next[index], [field]: value };
    if (field === 'label' && !next[index].key) {
        next[index].key = value
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_|_$/g, '');
    }
    items.value = next;
};
</script>

<template>
    <div
        :class="
            embedded
                ? 'space-y-3'
                : 'sm:col-span-2 rounded-xl border border-gray-200 bg-gray-50/80 p-4'
        "
    >
        <div class="mb-3 flex items-center justify-between gap-2">
            <div v-if="!embedded">
                <p class="text-sm font-semibold text-gray-800">Descartáveis do pedido</p>
                <p class="text-xs text-gray-500">O cliente escolhe quantidades na sacola (ex.: talheres, canudos).</p>
            </div>
            <button
                type="button"
                class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                :class="embedded ? 'ml-auto' : ''"
                @click="addItem"
            >
                + Item
            </button>
        </div>

        <p v-if="!items.length" class="text-sm text-gray-500">Nenhum item configurado.</p>

        <div v-for="(item, index) in items" :key="index" class="mb-3 grid gap-2 rounded-lg border border-gray-200 bg-white p-3 sm:grid-cols-6">
            <input
                :value="item.label"
                placeholder="Nome (ex.: Canudo)"
                class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm sm:col-span-2"
                @input="updateItem(index, 'label', $event.target.value)"
            />
            <input
                :value="item.key"
                placeholder="Chave (cutlery)"
                class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm font-mono text-xs sm:col-span-2"
                @input="updateItem(index, 'key', $event.target.value)"
            />
            <div class="flex items-center gap-1 text-xs text-gray-600">
                <label class="flex flex-col gap-0.5">
                    Mín
                    <input
                        :value="item.min_qty ?? 0"
                        type="number"
                        min="0"
                        max="99"
                        class="w-14 rounded border border-gray-300 px-1 py-1"
                        @input="updateItem(index, 'min_qty', parseInt($event.target.value, 10) || 0)"
                    />
                </label>
                <label class="flex flex-col gap-0.5">
                    Máx
                    <input
                        :value="item.max_qty ?? 5"
                        type="number"
                        min="0"
                        max="99"
                        class="w-14 rounded border border-gray-300 px-1 py-1"
                        @input="updateItem(index, 'max_qty', parseInt($event.target.value, 10) || 0)"
                    />
                </label>
                <label class="flex flex-col gap-0.5">
                    Padrão
                    <input
                        :value="item.default_qty ?? 0"
                        type="number"
                        min="0"
                        max="99"
                        class="w-14 rounded border border-gray-300 px-1 py-1"
                        @input="updateItem(index, 'default_qty', parseInt($event.target.value, 10) || 0)"
                    />
                </label>
            </div>
            <button type="button" class="text-xs text-red-600 hover:underline sm:col-span-6 sm:text-right" @click="removeItem(index)">
                Remover
            </button>
        </div>
    </div>
</template>
