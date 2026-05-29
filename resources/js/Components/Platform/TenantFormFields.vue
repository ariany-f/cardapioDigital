<script setup>
import DeliveryNotice from '@/Components/Platform/DeliveryNotice.vue';
import FormSection from '@/Components/Admin/FormSection.vue';
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    form: { type: Object, required: true },
    showSlug: { type: Boolean, default: false },
    showPlan: { type: Boolean, default: false },
    plans: { type: Array, default: () => [] },
    languages: { type: Array, default: () => [] },
    currencies: { type: Array, default: () => [] },
    timezones: { type: Array, default: () => [] },
    motoboysDisableBlocked: { type: Boolean, default: false },
    motoboyDeliveriesInProgressCount: { type: Number, default: 0 },
    planMotoboysIncluded: { type: Boolean, default: true },
    planPosIncluded: { type: Boolean, default: true },
    planKdsIncluded: { type: Boolean, default: true },
    inputClass: { type: String, default: 'platform-input' },
    labelClass: { type: String, default: 'mb-1 block text-sm font-medium text-slate-700' },
});

const page = usePage();
const delivery = computed(() => page.props.communication_disclaimer?.delivery ?? {});

const selectedPlan = computed(() =>
    props.showPlan && props.form.plan_id
        ? props.plans.find((p) => Number(p.id) === Number(props.form.plan_id))
        : null,
);

const planFeatureAllowed = (key, fallbackProp) => {
    if (selectedPlan.value?.features_json) {
        return selectedPlan.value.features_json[key] === true;
    }
    return fallbackProp;
};

const planAllowsMotoboys = computed(() => planFeatureAllowed('motoboys', props.planMotoboysIncluded));
const planAllowsPos = computed(() => planFeatureAllowed('pos', props.planPosIncluded));
const planAllowsKds = computed(() => planFeatureAllowed('kds', props.planKdsIncluded));

const plansT = () => page.props.platformTranslations?.plans ?? {};

watch(
    planAllowsMotoboys,
    (allowed) => {
        if (!allowed) {
            props.form.motoboys_enabled = false;
        }
    },
    { immediate: true },
);

watch(
    planAllowsPos,
    (allowed) => {
        if (!allowed) {
            props.form.pos_enabled = false;
        }
    },
    { immediate: true },
);

watch(
    planAllowsKds,
    (allowed) => {
        if (!allowed) {
            props.form.kds_enabled = false;
        }
    },
    { immediate: true },
);

const localeOptions = computed(() => {
    const list = [...props.languages];
    const current = props.form.default_locale;

    if (current && !list.some((l) => l.code === current)) {
        list.unshift({ code: current, name: current, flag: '' });
    }

    return list;
});

const currencyOptions = computed(() => {
    const list = [...props.currencies];
    const current = props.form.currency;

    if (current && !list.some((c) => c.code === current)) {
        list.unshift({ code: current, name: current });
    }

    return list;
});

const timezoneOptions = computed(() => {
    const list = [...props.timezones];
    const current = props.form.timezone;

    if (current && !list.some((tz) => tz.id === current)) {
        list.unshift({ id: current, label: current });
    }

    return list;
});
</script>

<template>
    <FormSection
        v-if="showPlan"
        title="Plano e assinatura"
        description="Plano contratado pelo restaurante na plataforma."
        variant="highlight"
        :columns="1"
    >
        <div>
            <label :class="labelClass">Plano *</label>
            <select v-model="form.plan_id" :class="inputClass" required>
                <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }} — R$ {{ p.price_monthly }}</option>
            </select>
        </div>
    </FormSection>

    <FormSection title="Identificação" description="Nome comercial, URL e dados fiscais do estabelecimento.">
        <div>
            <label :class="labelClass">Nome fantasia *</label>
            <input v-model="form.name" :class="inputClass" required />
        </div>
        <div v-if="showSlug">
            <label :class="labelClass">Slug (URL) *</label>
            <input v-model="form.slug" :class="inputClass" placeholder="auto se vazio" />
            <p class="mt-1 text-xs text-slate-500">Endereço público: /seu-slug</p>
        </div>
        <div v-else>
            <label :class="labelClass">Slug</label>
            <input v-model="form.slug" :class="inputClass" />
        </div>
        <div class="sm:col-span-2">
            <label :class="labelClass">Razão social</label>
            <input v-model="form.legal_name" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Tipo de documento</label>
            <select v-model="form.document_type" :class="inputClass">
                <option value="cnpj">CNPJ</option>
                <option value="cpf">CPF (MEI)</option>
            </select>
        </div>
        <div>
            <label :class="labelClass">CNPJ / CPF</label>
            <input v-model="form.document_number" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Inscrição estadual</label>
            <input v-model="form.state_registration" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Inscrição municipal</label>
            <input v-model="form.municipal_registration" :class="inputClass" />
        </div>
    </FormSection>

    <FormSection title="Contato" description="Canais de comunicação com o restaurante e redes sociais.">
        <div>
            <label :class="labelClass">Telefone</label>
            <input v-model="form.phone" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">WhatsApp</label>
            <input v-model="form.whatsapp" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">E-mail</label>
            <input v-model="form.email" type="email" :class="inputClass" />
        </div>
        <div>
            <label :class="labelClass">Site</label>
            <input v-model="form.website" :class="inputClass" placeholder="https://" />
        </div>
        <div class="sm:col-span-2">
            <label :class="labelClass">Instagram</label>
            <input v-model="form.instagram" :class="inputClass" placeholder="@usuario" />
            <p class="mt-1 text-xs text-slate-500">Usado nas páginas públicas quando a filial não tiver Instagram próprio.</p>
        </div>
    </FormSection>

    <FormSection title="Endereço da matriz" description="Endereço fiscal ou sede do restaurante.">
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
    </FormSection>

    <FormSection title="Cardápio público" description="Texto e preferências regionais exibidos ao cliente.">
        <div class="sm:col-span-2">
            <label :class="labelClass">Descrição pública</label>
            <textarea v-model="form.public_description" :class="inputClass" rows="3" placeholder="Apresentação na home do restaurante" />
        </div>
        <div>
            <label :class="labelClass">Idioma padrão</label>
            <select v-model="form.default_locale" :class="inputClass">
                <option v-if="!localeOptions.length" value="pt_BR">Português (Brasil)</option>
                <option v-for="lang in localeOptions" :key="lang.code" :value="lang.code">
                    {{ lang.flag ? `${lang.flag} ` : '' }}{{ lang.name }}
                </option>
            </select>
        </div>
        <div>
            <label :class="labelClass">Moeda</label>
            <select v-model="form.currency" :class="inputClass">
                <option v-if="!currencyOptions.length" value="BRL">Real brasileiro (BRL)</option>
                <option v-for="currency in currencyOptions" :key="currency.code" :value="currency.code">
                    {{ currency.name }}
                </option>
            </select>
        </div>
        <div>
            <label :class="labelClass">Fuso horário</label>
            <select v-model="form.timezone" :class="inputClass">
                <option v-if="!timezoneOptions.length" value="America/Sao_Paulo">
                    Brasília (UTC−3) — America/Sao_Paulo
                </option>
                <option v-for="tz in timezoneOptions" :key="tz.id" :value="tz.id">
                    {{ tz.label }}
                </option>
            </select>
        </div>
    </FormSection>

    <FormSection title="Identidade visual" description="Cores do tema no cardápio e painel do restaurante.">
        <div>
            <label :class="labelClass">Cor primária</label>
            <input v-model="form.theme_primary_color" type="color" class="h-10 w-full cursor-pointer rounded border border-slate-300" />
        </div>
        <div>
            <label :class="labelClass">Cor secundária</label>
            <input v-model="form.theme_secondary_color" type="color" class="h-10 w-full cursor-pointer rounded border border-slate-300" />
        </div>
    </FormSection>

    <FormSection
        title="Entrega e logística"
        description="O restaurante precisa de operação de entrega própria; a plataforma não fornece motoboys."
        :columns="1"
    >
        <DeliveryNotice variant="onboarding" />
    </FormSection>

    <FormSection
        title="Módulos do sistema"
        description="Ative ou desative funcionalidades disponíveis no painel deste restaurante."
        :columns="1"
    >
        <label
            class="flex flex-col gap-3 rounded-lg border p-3"
            :class="
                !planAllowsMotoboys || (motoboysDisableBlocked && form.motoboys_enabled)
                    ? 'border-slate-200 bg-slate-50'
                    : 'cursor-pointer border-slate-200 bg-white'
            "
        >
            <span class="flex items-start gap-3">
                <input
                    v-model="form.motoboys_enabled"
                    type="checkbox"
                    class="mt-1 rounded border-slate-300"
                    :disabled="
                        !planAllowsMotoboys || (motoboysDisableBlocked && form.motoboys_enabled)
                    "
                />
                <span>
                    <span class="block text-sm font-semibold text-slate-900">Módulo de entregadores</span>
                    <span class="mt-1 block text-xs text-slate-600">
                        Ferramenta para o restaurante cadastrar a própria equipe (não há entregadores do App
                        Cardápio). Desligado: sem painel do entregador; pedidos de entrega seguem com status manual.
                    </span>
                </span>
            </span>
            <DeliveryNotice v-if="planAllowsMotoboys" variant="motoboys_module" class="!border-slate-200 !bg-white" />
            <p v-if="!planAllowsMotoboys" class="text-xs font-medium text-amber-800">
                {{
                    showPlan
                        ? delivery.motoboys_plan_blocked
                        : delivery.motoboys_plan_blocked_edit
                }}
            </p>
            <p
                v-if="motoboysDisableBlocked && form.motoboys_enabled"
                class="text-xs font-medium text-amber-800"
            >
                {{
                    motoboyDeliveriesInProgressCount === 1
                        ? 'Há 1 entrega com entregador em andamento. Finalize-a para desativar o módulo (pedidos futuros não serão afetados pela espera).'
                        : `Há ${motoboyDeliveriesInProgressCount} entregas com entregador em andamento. Finalize-as para desativar o módulo.`
                }}
            </p>
            <p v-if="form.errors.motoboys_enabled" class="text-xs font-medium text-red-600">
                {{ form.errors.motoboys_enabled }}
            </p>
        </label>
        <label
            class="flex flex-col gap-3 rounded-lg border p-3"
            :class="planAllowsPos ? 'cursor-pointer border-slate-200 bg-white' : 'border-slate-200 bg-slate-50'"
        >
            <span class="flex items-start gap-3">
                <input
                    v-model="form.pos_enabled"
                    type="checkbox"
                    class="mt-1 rounded border-slate-300"
                    :disabled="!planAllowsPos"
                />
                <span>
                    <span class="block text-sm font-semibold text-slate-900">PDV (balcão)</span>
                    <span class="mt-1 block text-xs text-slate-600">
                        Desligado: o menu PDV some do painel. Pedidos online e demais funções continuam normais.
                    </span>
                </span>
            </span>
            <p v-if="!planAllowsPos" class="text-xs font-medium text-amber-800">
                {{ showPlan ? plansT().pos_plan_blocked : plansT().pos_plan_blocked_edit }}
            </p>
            <p v-if="form.errors.pos_enabled" class="text-xs font-medium text-red-600">{{ form.errors.pos_enabled }}</p>
        </label>
        <label
            class="flex flex-col gap-3 rounded-lg border p-3"
            :class="planAllowsKds ? 'cursor-pointer border-slate-200 bg-white' : 'border-slate-200 bg-slate-50'"
        >
            <span class="flex items-start gap-3">
                <input
                    v-model="form.kds_enabled"
                    type="checkbox"
                    class="mt-1 rounded border-slate-300"
                    :disabled="!planAllowsKds"
                />
                <span>
                    <span class="block text-sm font-semibold text-slate-900">KDS (cozinha)</span>
                    <span class="mt-1 block text-xs text-slate-600">
                        Desligado: painel da cozinha some do menu. A produção pode ser acompanhada na tela de pedidos.
                    </span>
                </span>
            </span>
            <p v-if="!planAllowsKds" class="text-xs font-medium text-amber-800">
                {{ showPlan ? plansT().kds_plan_blocked : plansT().kds_plan_blocked_edit }}
            </p>
            <p v-if="form.errors.kds_enabled" class="text-xs font-medium text-red-600">{{ form.errors.kds_enabled }}</p>
        </label>
    </FormSection>
</template>
