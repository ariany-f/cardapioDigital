<script setup>
const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    inputClass: { type: String, default: 'admin-input' },
    accentClass: { type: String, default: 'admin' },
});

const emit = defineEmits(['update:modelValue']);

const types = [
    { value: 'choice', label: 'Escolha' },
    { value: 'addon', label: 'Adicional' },
    { value: 'disposable', label: 'Descartável (item)' },
];

const update = (groups) => emit('update:modelValue', groups);

const addGroup = () => {
    update([
        ...(props.modelValue ?? []),
        {
            name: '',
            type: 'addon',
            min_select: 0,
            max_select: 3,
            allow_quantity: false,
            options: [{ name: '', additional_price: 0, max_quantity: 3 }],
        },
    ]);
};

const removeGroup = (gi) => update((props.modelValue ?? []).filter((_, i) => i !== gi));

const patchGroup = (gi, field, value) => {
    const next = [...(props.modelValue ?? [])];
    next[gi] = { ...next[gi], [field]: value };
    if (field === 'type' && value === 'disposable') {
        next[gi].allow_quantity = true;
    }
    update(next);
};

const addOption = (gi) => {
    const next = [...(props.modelValue ?? [])];
    next[gi] = {
        ...next[gi],
        options: [...(next[gi].options ?? []), { name: '', additional_price: 0, max_quantity: 3 }],
    };
    update(next);
};

const removeOption = (gi, oi) => {
    const next = [...(props.modelValue ?? [])];
    next[gi] = { ...next[gi], options: next[gi].options.filter((_, i) => i !== oi) };
    update(next);
};

const patchOption = (gi, oi, field, value) => {
    const next = [...(props.modelValue ?? [])];
    const opts = [...next[gi].options];
    opts[oi] = { ...opts[oi], [field]: value };
    next[gi] = { ...next[gi], options: opts };
    update(next);
};
</script>

<template>
    <div class="sm:col-span-2 rounded-2xl border border-dashed border-orange-200 bg-orange-50/50 p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-stone-800">Opções do produto</p>
                <p class="text-xs text-stone-500">Escolhas, adicionais e descartáveis com quantidade por opção.</p>
            </div>
            <button type="button" :class="accentClass === 'platform' ? 'platform-btn-primary' : 'admin-btn-primary'" @click="addGroup">
                + Grupo
            </button>
        </div>

        <div v-for="(group, gi) in modelValue" :key="gi" class="mb-4 rounded-xl border border-stone-200 bg-white p-4">
            <div class="grid gap-3 sm:grid-cols-3">
                <input
                    :value="group.name"
                    placeholder="Nome do grupo"
                    :class="[inputClass, 'sm:col-span-2']"
                    @input="patchGroup(gi, 'name', $event.target.value)"
                />
                <select
                    :value="group.type"
                    :class="inputClass"
                    @change="patchGroup(gi, 'type', $event.target.value)"
                >
                    <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
                <label class="flex items-center gap-2 text-sm text-stone-600">
                    Mín
                    <input
                        :value="group.min_select"
                        type="number"
                        min="0"
                        :class="[inputClass, '!w-20']"
                        @input="patchGroup(gi, 'min_select', parseInt($event.target.value, 10) || 0)"
                    />
                </label>
                <label class="flex items-center gap-2 text-sm text-stone-600">
                    Máx opções
                    <input
                        :value="group.max_select"
                        type="number"
                        min="1"
                        :class="[inputClass, '!w-20']"
                        @input="patchGroup(gi, 'max_select', parseInt($event.target.value, 10) || 1)"
                    />
                </label>
                <label class="flex items-center gap-2 text-sm text-stone-700">
                    <input
                        type="checkbox"
                        class="rounded border-stone-300"
                        :checked="group.allow_quantity"
                        @change="patchGroup(gi, 'allow_quantity', $event.target.checked)"
                    />
                    Qtd por opção
                </label>
            </div>

            <div class="mt-3 space-y-2">
                <div
                    v-for="(opt, oi) in group.options"
                    :key="oi"
                    class="flex flex-wrap items-center gap-2 rounded-xl bg-stone-50 px-3 py-2"
                >
                    <input
                        :value="opt.name"
                        placeholder="Opção"
                        :class="[inputClass, 'min-w-[8rem] flex-1']"
                        @input="patchOption(gi, oi, 'name', $event.target.value)"
                    />
                    <label class="flex items-center gap-1 text-sm text-stone-600">
                        +R$
                        <input
                            :value="opt.additional_price"
                            type="number"
                            step="0.01"
                            min="0"
                            :class="[inputClass, '!w-24']"
                            @input="patchOption(gi, oi, 'additional_price', parseFloat($event.target.value) || 0)"
                        />
                    </label>
                    <label v-if="group.allow_quantity" class="flex items-center gap-1 text-sm text-stone-600">
                        Máx qtd
                        <input
                            :value="opt.max_quantity ?? 3"
                            type="number"
                            min="1"
                            max="99"
                            :class="[inputClass, '!w-20']"
                            @input="patchOption(gi, oi, 'max_quantity', parseInt($event.target.value, 10) || 1)"
                        />
                    </label>
                    <button type="button" class="text-sm text-red-600 hover:underline" @click="removeOption(gi, oi)">×</button>
                </div>
                <button type="button" :class="[accentClass === 'platform' ? 'text-indigo-600' : 'text-orange-600', 'text-sm font-semibold hover:underline']" @click="addOption(gi)">
                    + Opção
                </button>
            </div>

            <button type="button" class="mt-2 text-sm text-red-600 hover:underline" @click="removeGroup(gi)">Remover grupo</button>
        </div>
    </div>
</template>
