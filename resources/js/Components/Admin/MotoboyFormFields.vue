<script setup>
import DeliveryNotice from '@/Components/Platform/DeliveryNotice.vue';
import FormSection from '@/Components/Admin/FormSection.vue';

defineProps({
    form: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    formOptions: { type: Object, required: true },
    inputClass: { type: String, default: 'admin-input' },
    labelClass: { type: String, default: 'mb-1 block text-sm font-medium text-stone-700' },
});

defineEmits(['toggle-branch']);
</script>

<template>
    <DeliveryNotice variant="motoboy_admin" class="mb-4" />

    <FormSection
        title="Tipo de entregador"
        description="Define se o entregador usa o painel no celular ou só comanda impressa."
        variant="highlight"
        :columns="1"
    >
        <div class="flex flex-col gap-2 sm:flex-row sm:gap-6">
            <label class="flex cursor-pointer items-start gap-2 text-sm text-stone-800">
                <input v-model="form.uses_app" type="radio" :value="true" class="mt-1" />
                <span>
                    <strong>Com painel web</strong> — aceita/recusa pedidos e confirma entrega com código no celular.
                </span>
            </label>
            <label class="flex cursor-pointer items-start gap-2 text-sm text-stone-800">
                <input v-model="form.uses_app" type="radio" :value="false" class="mt-1" />
                <span>
                    <strong>Só comanda impressa</strong> — sem login; você imprime e atualiza o status no admin.
                </span>
            </label>
        </div>
    </FormSection>

    <FormSection title="Dados pessoais" description="Identificação e contato do entregador.">
        <div>
            <label :class="labelClass">Nome completo *</label>
            <input v-model="form.name" :class="inputClass" required />
        </div>
        <div>
            <label :class="labelClass">Telefone / WhatsApp *</label>
            <input v-model="form.phone" :class="inputClass" placeholder="(11) 99999-9999" required />
        </div>
        <div>
            <label :class="labelClass">CPF</label>
            <input v-model="form.cpf" :class="inputClass" placeholder="000.000.000-00" />
        </div>
        <div>
            <label :class="labelClass">RG</label>
            <input v-model="form.document_rg" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Data de nascimento</label>
            <input v-model="form.birth_date" type="date" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Código interno</label>
            <input v-model="form.employee_code" :class="inputClass" placeholder="Ex: ENT-001" />
        </div>
        <template v-if="form.uses_app">
            <div>
                <label :class="labelClass">E-mail (login do app) *</label>
                <input v-model="form.email" type="email" :class="inputClass" required />
            </div>
            <div>
                <label :class="labelClass">Senha do painel</label>
                <input v-model="form.password" type="password" :class="inputClass" placeholder="Obrigatória ao cadastrar" />
            </div>
        </template>
    </FormSection>

    <FormSection
        v-if="branches?.length"
        title="Filiais atendidas"
        description="Unidades em que este entregador pode receber pedidos."
        :columns="1"
    >
        <label class="flex cursor-pointer items-center gap-2 text-sm text-stone-700">
            <input v-model="form.access_all_branches" type="checkbox" class="rounded border-stone-300" />
            Atende todas as filiais do restaurante
        </label>
        <div v-if="!form.access_all_branches" class="rounded-lg border border-stone-200 bg-white p-3">
            <p class="mb-2 text-xs font-medium text-stone-600">Selecione as filiais</p>
            <label v-for="b in branches" :key="b.id" class="flex cursor-pointer items-center gap-2 py-1 text-sm">
                <input type="checkbox" :checked="form.branch_ids.includes(b.id)" @change="$emit('toggle-branch', b.id)" />
                {{ b.name }}
            </label>
        </div>
    </FormSection>

    <FormSection title="Endereço" description="Residência ou base do entregador (opcional).">
        <div class="sm:col-span-2">
            <label :class="labelClass">Rua</label>
            <input v-model="form.street" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Número</label>
            <input v-model="form.number" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Complemento</label>
            <input v-model="form.complement" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Bairro</label>
            <input v-model="form.neighborhood" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Cidade</label>
            <input v-model="form.city" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">UF</label>
            <input v-model="form.state" :class="inputClass" maxlength="2" placeholder="SP" />
        </div>
        <div>
            <label :class="labelClass">CEP</label>
            <input v-model="form.postal_code" :class="inputClass" />
        </div>
    </FormSection>

    <FormSection title="Contato de emergência" description="Pessoa para ligar em caso de imprevisto.">
        <div>
            <label :class="labelClass">Nome</label>
            <input v-model="form.emergency_contact_name" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Telefone</label>
            <input v-model="form.emergency_contact_phone" :class="inputClass" />
        </div>
    </FormSection>

    <FormSection title="Veículo e documentação" description="Dados do veículo e habilitação para entrega.">
        <div>
            <label :class="labelClass">Tipo de veículo *</label>
            <select v-model="form.vehicle_type" :class="inputClass" required>
                <option v-for="o in formOptions.vehicle_types" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
        </div>
        <div>
            <label :class="labelClass">Modelo / descrição</label>
            <input v-model="form.vehicle" :class="inputClass" placeholder="Ex: Honda CG 160 — vermelha" />
        </div>
        <div>
            <label :class="labelClass">Placa</label>
            <input v-model="form.license_plate" :class="inputClass" placeholder="ABC-1D23" />
        </div>
        <div>
            <label :class="labelClass">CNH</label>
            <input v-model="form.cnh_number" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Categoria CNH</label>
            <input v-model="form.cnh_category" :class="inputClass" placeholder="A, AB, B..." />
        </div>
        <div>
            <label :class="labelClass">Validade CNH</label>
            <input v-model="form.cnh_expires_at" type="date" :class="inputClass" />
        </div>
    </FormSection>

    <FormSection title="Contrato e pagamento" description="Vínculo trabalhista e repasse ao entregador.">
        <div>
            <label :class="labelClass">Vínculo *</label>
            <select v-model="form.employment_type" :class="inputClass" required>
                <option v-for="o in formOptions.employment_types" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
        </div>
        <div>
            <label :class="labelClass">Data de contratação</label>
            <input v-model="form.hired_at" type="date" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Comissão (%)</label>
            <input v-model="form.commission_percent" type="number" step="0.01" min="0" max="100" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Tipo chave Pix</label>
            <select v-model="form.pix_key_type" :class="inputClass">
                <option value="">—</option>
                <option v-for="o in formOptions.pix_key_types" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label :class="labelClass">Chave Pix</label>
            <input v-model="form.pix_key" :class="inputClass" />
        </div>
    </FormSection>

    <FormSection title="Operação" description="Disponibilidade para novas entregas e limites.">
        <div>
            <label :class="labelClass">Status operacional *</label>
            <select v-model="form.operational_status" :class="inputClass" required>
                <option v-for="o in formOptions.operational_statuses" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
        </div>
        <div>
            <label :class="labelClass">Máx. entregas simultâneas *</label>
            <input v-model="form.max_active_deliveries" type="number" min="1" max="20" :class="inputClass" required />
        </div>
        <div class="sm:col-span-2">
            <label class="flex items-center gap-2 text-sm text-stone-700">
                <input v-model="form.is_active" type="checkbox" class="rounded border-stone-300" />
                Cadastro ativo (pode receber novas entregas)
            </label>
        </div>
        <div class="sm:col-span-2">
            <label :class="labelClass">Observações internas</label>
            <textarea v-model="form.notes" rows="3" :class="inputClass" placeholder="Horários preferenciais, restrições, acordos..." />
        </div>
    </FormSection>
</template>
