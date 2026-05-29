<script setup>
import FormSection from '@/Components/Admin/FormSection.vue';
import OrderDisposablesEditor from '@/Components/Admin/OrderDisposablesEditor.vue';
import OpeningHoursEditor from '@/Components/Platform/OpeningHoursEditor.vue';

defineProps({
    form: { type: Object, required: true },
    showSlug: { type: Boolean, default: true },
    coverPreview: { type: String, default: null },
    inputClass: { type: String, default: 'platform-input' },
    labelClass: { type: String, default: 'mb-1 block text-sm font-medium text-slate-700' },
});

const emit = defineEmits(['cover-change']);

const onCover = (e) => emit('cover-change', e.target.files[0] ?? null);
</script>

<template>
    <FormSection
        title="Identificação e contato"
        description="Como a unidade aparece para o cliente e como você recebe avisos."
    >
        <div>
            <label :class="labelClass">Nome da unidade *</label>
            <input v-model="form.name" :class="inputClass" required />
        </div>
        <div v-if="showSlug">
            <label :class="labelClass">Slug (URL)</label>
            <input v-model="form.slug" :class="inputClass" placeholder="auto se vazio" />
        </div>
        <div>
            <label :class="labelClass">Telefone</label>
            <input v-model="form.phone" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Instagram da filial</label>
            <input v-model="form.instagram" :class="inputClass" placeholder="@usuario ou link" />
            <p class="mt-1 text-xs text-stone-500">Se vazio, usa o Instagram do restaurante na página pública.</p>
        </div>
        <div>
            <label :class="labelClass">E-mail de pedidos</label>
            <input v-model="form.notification_email" type="email" :class="inputClass" />
        </div>
        <div class="sm:col-span-2">
            <label :class="labelClass">Descrição pública</label>
            <textarea v-model="form.public_description" :class="inputClass" rows="2" placeholder="Texto exibido no cardápio da unidade" />
        </div>
    </FormSection>

    <FormSection title="Endereço e localização" description="Endereço físico e coordenadas para entrega e mapa.">
        <div class="sm:col-span-2">
            <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="form.street" :class="inputClass" placeholder="Rua / Av." />
                <input v-model="form.number" :class="inputClass" placeholder="Número" />
                <input v-model="form.complement" :class="inputClass" placeholder="Complemento" />
                <input v-model="form.neighborhood" :class="inputClass" placeholder="Bairro" />
                <input v-model="form.city" :class="inputClass" placeholder="Cidade" />
                <input v-model="form.state" :class="inputClass" placeholder="UF" maxlength="2" />
                <input v-model="form.postal_code" :class="inputClass" placeholder="CEP" />
            </div>
        </div>
        <div>
            <label :class="labelClass">Latitude</label>
            <input v-model="form.latitude" type="number" step="any" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Longitude</label>
            <input v-model="form.longitude" type="number" step="any" :class="inputClass" />
        </div>
    </FormSection>

    <FormSection title="Horário de funcionamento" description="Quando a unidade aceita pedidos pelo cardápio." :columns="1">
        <OpeningHoursEditor v-model="form.opening_hours" />
    </FormSection>

    <FormSection
        title="Descartáveis no pedido"
        description="Talheres, guardanapos etc. que o cliente pode solicitar no checkout."
        :columns="1"
    >
        <OrderDisposablesEditor v-model="form.order_disposables" embedded />
    </FormSection>

    <FormSection
        title="Pedidos e cardápio"
        description="Tipos de pedido aceitos e como novos pedidos entram no painel."
    >
        <div class="sm:col-span-2 flex flex-wrap gap-4 text-sm">
            <label class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox" /> Unidade ativa</label>
            <label class="flex items-center gap-2"><input v-model="form.pickup_available" type="checkbox" /> Retirada</label>
            <label class="flex items-center gap-2"><input v-model="form.delivery_available" type="checkbox" /> Entrega</label>
            <label class="flex items-center gap-2"><input v-model="form.allow_scheduled_orders" type="checkbox" /> Pedido agendado</label>
            <label class="flex items-center gap-2"><input v-model="form.auto_print_on_new_order" type="checkbox" /> Imprimir ao receber</label>
        </div>
        <div class="sm:col-span-2">
            <label class="flex cursor-pointer items-start gap-2 text-sm text-stone-700">
                <input v-model="form.auto_accept_orders" type="checkbox" class="mt-0.5" />
                <span>
                    <strong>Aprovação automática</strong> — pedidos do cardápio já entram confirmados. Desmarcado, ficam
                    em “aguardando confirmação” até aprovar no painel ou KDS.
                </span>
            </label>
        </div>
    </FormSection>

    <FormSection title="Entrega e valores" description="Regras comerciais para pedidos nesta unidade.">
        <div>
            <label :class="labelClass">Raio de entrega (km)</label>
            <input v-model="form.delivery_radius_km" type="number" step="0.5" min="0" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Pedido mínimo (R$)</label>
            <input v-model="form.minimum_order_amount" type="number" step="0.01" min="0" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Taxa de embalagem (R$)</label>
            <input v-model="form.packaging_fee_default" type="number" step="0.01" min="0" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Tempo de preparo padrão (min)</label>
            <input v-model="form.default_prep_time_minutes" type="number" min="1" :class="inputClass" />
            <p class="mt-1 text-xs text-slate-500">Usado quando o produto não tiver tempo próprio.</p>
        </div>
        <div>
            <label :class="labelClass">Tempo de entrega (min)</label>
            <input v-model="form.delivery_time_minutes" type="number" min="0" :class="inputClass" />
            <p class="mt-1 text-xs text-slate-500">Deslocamento até o cliente (somado ao preparo na previsão).</p>
        </div>
    </FormSection>

    <FormSection title="Impressão de pedidos" description="Formato da comanda na cozinha ou balcão.">
        <div>
            <label :class="labelClass">Formato</label>
            <select v-model="form.print_format" :class="inputClass">
                <option value="thermal_80mm">Térmica 80mm</option>
                <option value="thermal_58mm">Térmica 58mm</option>
                <option value="a4_summary">A4 resumo</option>
            </select>
        </div>
        <div>
            <label :class="labelClass">Vias de impressão</label>
            <input v-model="form.print_copies_default" type="number" min="1" max="5" :class="inputClass" />
        </div>
    </FormSection>

    <FormSection title="Aparência no cardápio" description="Imagem de capa exibida no topo do cardápio público." :columns="1">
        <div>
            <label :class="labelClass">Imagem do hero</label>
            <input type="file" accept="image/*" class="mt-1 block w-full text-sm" @change="onCover" />
            <img v-if="coverPreview" :src="coverPreview" alt="Capa" class="mt-3 h-32 w-full max-w-md rounded-lg object-cover" />
        </div>
    </FormSection>
</template>
