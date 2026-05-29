<script setup>
import FormSection from '@/Components/Admin/FormSection.vue';

defineProps({
    form: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    inputClass: { type: String, default: 'admin-input' },
    labelClass: { type: String, default: 'mb-1 block text-sm font-medium text-stone-700' },
});

defineEmits(['add-item']);
</script>

<template>
    <FormSection
        title="Informações do combo"
        description="Nome, preço e em quais unidades o combo aparece no cardápio."
    >
        <div>
            <label :class="labelClass">Nome *</label>
            <input v-model="form.name" :class="inputClass" placeholder="Ex: Combo Família" required />
        </div>
        <div>
            <label :class="labelClass">Preço (R$) *</label>
            <input v-model="form.price" type="number" step="0.01" min="0" :class="inputClass" required />
        </div>
        <div>
            <label :class="labelClass">Filial</label>
            <select v-model="form.branch_id" :class="inputClass">
                <option value="">Todas as filiais</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
        </div>
        <div>
            <label :class="labelClass">Status</label>
            <label class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-stone-700">
                <input v-model="form.is_active" type="checkbox" class="rounded border-stone-300" />
                Combo ativo no cardápio
            </label>
        </div>
        <div class="sm:col-span-2">
            <label :class="labelClass">Descrição</label>
            <textarea v-model="form.description" :class="inputClass" rows="2" placeholder="O que vem no combo (opcional)" />
        </div>
        <div class="sm:col-span-2">
            <label :class="labelClass">Imagem</label>
            <input type="file" accept="image/*" class="mt-1 block w-full text-sm text-stone-600" @change="form.image = $event.target.files[0]" />
        </div>
    </FormSection>

    <FormSection
        title="Itens incluídos"
        description="Produtos que compõem o combo e a quantidade de cada um."
        :columns="1"
    >
        <div
            v-for="(item, i) in form.items"
            :key="i"
            class="flex flex-wrap items-end gap-2 rounded-lg border border-stone-200 bg-white p-3"
        >
            <div class="min-w-0 flex-1">
                <label :class="labelClass">Produto *</label>
                <select v-model="item.product_id" :class="inputClass" required>
                    <option value="" disabled>Selecione</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </div>
            <div class="w-24 shrink-0">
                <label :class="labelClass">Qtd.</label>
                <input v-model="item.quantity" type="number" min="1" :class="inputClass" />
            </div>
        </div>
        <button type="button" class="text-sm font-medium text-orange-600 hover:text-orange-700" @click="$emit('add-item')">
            + Adicionar produto ao combo
        </button>
    </FormSection>
</template>
