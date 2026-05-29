<script setup>
import OrderActionsMenu from '@/Components/Admin/OrderActionsMenu.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    order: Object,
    motoboys: { type: Array, default: () => [] },
    motoboys_enabled: { type: Boolean, default: true },
});

const page = usePage();
const tenant = page.props.tenant;
const { can } = usePermissions();

const showCancelModal = ref(false);
const cancelMode = ref('cancel');

const statusLabel = (status) => page.props.translations?.[`order.status.${status}`] ?? status;

const isPending = computed(() => props.order.status === 'pending_approval');
const isTerminal = computed(() => ['cancelled', 'rejected', 'delivered'].includes(props.order.status));
const canAdvance = computed(() => !isTerminal.value && !isPending.value);

const cancelForm = useForm({ cancel_reason: '' });

const accept = () => {
    if (!confirm('Aprovar este pedido?')) return;
    router.post(route('tenant.admin.orders.accept', { tenant: tenant.slug, order: props.order.id }));
};

const openReject = () => {
    cancelMode.value = 'reject';
    cancelForm.cancel_reason = '';
    showCancelModal.value = true;
};

const openCancel = () => {
    cancelMode.value = 'cancel';
    cancelForm.cancel_reason = '';
    showCancelModal.value = true;
};

const submitCancel = () => {
    const routeName =
        cancelMode.value === 'reject' ? 'tenant.admin.orders.reject' : 'tenant.admin.orders.cancel';
    cancelForm.post(route(routeName, { tenant: tenant.slug, order: props.order.id }), {
        onSuccess: () => {
            showCancelModal.value = false;
            cancelForm.reset();
        },
    });
};

const setStatus = (status) =>
    router.patch(route('tenant.admin.orders.status', { tenant: tenant.slug, order: props.order.id }), { status });

const deliveryForm = useForm({
    motoboy_id: props.order.delivery?.motoboy_id ?? '',
    delivery_status: props.order.delivery?.status ?? 'pending',
    confirmation_code: '',
});

const confirmDeliveryForm = useForm({
    confirmation_code: '',
});

const isDelivery = computed(() => props.order.type === 'delivery');
const awaitingDeliveryCode = computed(
    () => isDelivery.value && props.order.status === 'out_for_delivery',
);
const hasDeliveryCode = computed(() => !!props.order.delivery_confirmation_code);
const isDeliveryFinalized = computed(() => props.order.status === 'delivered');
const showDeliveryEditModal = ref(false);

const deliveryStatusLabel = (status) => {
    const map = {
        pending: 'Aguardando',
        assigned: 'Atribuído',
        picked_up: 'Retirado',
        on_route: 'A caminho',
        delivered: 'Entregue',
        failed: 'Falhou',
    };
    return map[status] ?? status;
};

const formattedDeliveryAddress = computed(() => {
    const a = props.order.delivery_address;
    if (!a) {
        return null;
    }
    const line1 = [a.street, a.number].filter(Boolean).join(', ');
    const line2 = [a.complement, a.neighborhood].filter(Boolean).join(' — ');
    const line3 = [a.city, a.state, a.postal_code].filter(Boolean).join(' · ');
    return [line1, line2, line3].filter(Boolean).join(' · ');
});

const openDeliveryEdit = () => {
    deliveryForm.motoboy_id = props.order.delivery?.motoboy_id ?? '';
    deliveryForm.delivery_status = props.order.delivery?.status ?? 'delivered';
    deliveryForm.confirmation_code = '';
    showDeliveryEditModal.value = true;
};

const saveDelivery = () => {
    if (deliveryForm.delivery_status === 'delivered' && !deliveryForm.confirmation_code && !isDeliveryFinalized.value) {
        return;
    }
    deliveryForm.patch(route('tenant.admin.orders.delivery', { tenant: tenant.slug, order: props.order.id }), {
        onSuccess: () => {
            deliveryForm.reset('confirmation_code');
            showDeliveryEditModal.value = false;
        },
    });
};

const submitConfirmDelivery = () =>
    confirmDeliveryForm.post(
        route('tenant.admin.orders.confirm-delivery', { tenant: tenant.slug, order: props.order.id }),
        { onSuccess: () => confirmDeliveryForm.reset() },
    );

const motoboyLabel = (m) => {
    const parts = [m.name];
    parts.push(m.uses_app ? '📱 App' : '🖨️ Impresso');
    if (m.phone) parts.push(m.phone);
    if (m.uses_app && m.active_deliveries_count != null) {
        parts.push(`${m.active_deliveries_count}/${m.max_active_deliveries} entregas`);
    }
    return parts.join(' · ');
};

const vehicleLabel = (type) => {
    const map = { motorcycle: 'Moto', bicycle: 'Bicicleta', car: 'Carro', van: 'Van', on_foot: 'A pé' };
    return map[type] ?? type;
};

const assignedMotoboy = computed(() => props.order.delivery?.motoboy);

const deliveryStatusHistories = computed(() => props.order.delivery?.status_histories ?? []);

const motoboyAssignmentLabel = (status) => {
    const map = {
        pending: 'Aguardando aceite',
        accepted: 'Aceito',
        rejected: 'Recusado',
    };
    return map[status] ?? status;
};

const markPaid = () => {
    if (!confirm('Confirmar que o pagamento foi recebido?')) return;
    router.post(route('tenant.admin.orders.paid', { tenant: tenant.slug, order: props.order.id }));
};

const paymentStatusLabel = (status) => {
    const map = {
        pending: 'Pendente',
        paid: 'Pago',
        refunded: 'Estornado',
    };
    return map[status] ?? status;
};

const revertPaymentForm = useForm({ reason: '' });
const showRevertPaymentModal = ref(false);
const showStatusCorrectionModal = ref(false);

const submitRevertPayment = () => {
    revertPaymentForm.post(
        route('tenant.admin.orders.revert-payment', { tenant: tenant.slug, order: props.order.id }),
        {
            onSuccess: () => {
                showRevertPaymentModal.value = false;
                revertPaymentForm.reset();
            },
        },
    );
};

const statusCorrectionForm = useForm({
    status: props.order.status,
    reason: '',
});

const statusOptions = [
    'pending_approval',
    'confirmed',
    'preparing',
    'ready',
    'out_for_delivery',
    'delivered',
    'cancelled',
    'rejected',
];

const submitStatusCorrection = () => {
    if (!confirm('Alterar o status deste pedido? A ação ficará registrada no histórico.')) return;
    statusCorrectionForm.patch(
        route('tenant.admin.orders.correct-status', { tenant: tenant.slug, order: props.order.id }),
        {
            onSuccess: () => {
                statusCorrectionForm.reset('reason');
                showStatusCorrectionModal.value = false;
            },
        },
    );
};
</script>

<template>
    <Head :title="order.order_number" />

    <Link :href="route('tenant.admin.orders.index', { tenant: tenant.slug })" class="text-sm text-stone-500">← Pedidos</Link>

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="admin-page-title">{{ order.order_number }}</h1>
            <p class="text-stone-500">{{ order.guest_name }} — {{ order.guest_phone }}</p>
            <p class="mt-1 text-sm">
                <span
                    class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="
                        isPending
                            ? 'bg-amber-100 text-amber-900'
                            : isTerminal
                              ? 'bg-stone-200 text-stone-700'
                              : 'bg-orange-100 text-orange-900'
                    "
                >
                    {{ statusLabel(order.status) }}
                </span>
                <span
                    class="ml-2 inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="
                        order.payment_status === 'paid'
                            ? 'bg-green-100 text-green-800'
                            : order.payment_status === 'refunded'
                              ? 'bg-stone-200 text-stone-700'
                              : 'bg-stone-100 text-stone-600'
                    "
                >
                    Pagamento: {{ paymentStatusLabel(order.payment_status) }}
                </span>
                <span class="ml-2 text-stone-500">{{ order.branch?.name }}</span>
            </p>
            <p v-if="order.cancel_reason" class="mt-2 text-sm text-red-700">
                Motivo: {{ order.cancel_reason }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <OrderActionsMenu
                :can-manage="can('orders.accept')"
                :payment-paid="order.payment_status === 'paid'"
                :show-edit-delivery="isDelivery && isDeliveryFinalized"
                @revert-payment="showRevertPaymentModal = true"
                @correct-status="showStatusCorrectionModal = true"
                @edit-delivery="openDeliveryEdit"
            />
            <a
                :href="route('tenant.admin.orders.print', { tenant: tenant.slug, order: order.id })"
                target="_blank"
                rel="noopener"
                class="admin-btn-secondary"
            >
                Imprimir
            </a>
            <button
                v-if="order.payment_status !== 'paid' && can('orders.accept')"
                type="button"
                class="admin-btn-primary text-sm"
                @click="markPaid"
            >
                Confirmar pagamento
            </button>
            <button
                v-if="isPending && can('orders.accept')"
                type="button"
                class="rounded-xl bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700"
                @click="accept"
            >
                Aprovar pedido
            </button>
            <button
                v-if="isPending && can('orders.cancel')"
                type="button"
                class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
                @click="openReject"
            >
                Recusar
            </button>
            <template v-if="canAdvance && can('orders.accept')">
                <button type="button" class="admin-btn-secondary text-sm" @click="setStatus('preparing')">Em preparo</button>
                <button type="button" class="admin-btn-secondary text-sm" @click="setStatus('ready')">Pronto</button>
                <button
                    v-if="order.type === 'delivery'"
                    type="button"
                    class="admin-btn-secondary text-sm"
                    @click="setStatus('out_for_delivery')"
                >
                    Saiu para entrega
                </button>
                <button
                    v-if="!isDelivery"
                    type="button"
                    class="admin-btn-secondary text-sm"
                    @click="setStatus('delivered')"
                >
                    Entregue
                </button>
            </template>
            <button
                v-if="!isTerminal && can('orders.cancel')"
                type="button"
                class="rounded-xl bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700"
                @click="openCancel"
            >
                Cancelar pedido
            </button>
        </div>
    </div>

    <div class="admin-card mt-6">
        <h2 class="font-semibold">Itens</h2>
        <ul class="mt-3 divide-y">
            <li v-for="item in order.items" :key="item.id" class="py-2">
                <div class="flex justify-between">
                    <span>{{ item.quantity }}x {{ item.name }}</span>
                    <span>R$ {{ parseFloat(item.total_price).toFixed(2) }}</span>
                </div>
                <ul v-if="item.variations_snapshot?.length" class="mt-1 space-y-0.5 text-xs text-stone-500">
                    <li v-for="(v, i) in item.variations_snapshot" :key="i">
                        {{ v.option_name }}{{ v.quantity > 1 ? ` ×${v.quantity}` : '' }}
                    </li>
                </ul>
            </li>
        </ul>
        <p class="mt-4 border-t pt-4 text-right text-lg font-bold">Total: R$ {{ parseFloat(order.total).toFixed(2) }}</p>
    </div>

    <div v-if="isDelivery" class="admin-card mt-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold">Entrega</h2>
            <button
                v-if="isDeliveryFinalized && can('orders.accept')"
                type="button"
                class="text-sm font-medium text-stone-600 underline-offset-2 hover:text-stone-900 hover:underline"
                @click="openDeliveryEdit"
            >
                Editar entrega
            </button>
        </div>

        <template v-if="isDeliveryFinalized">
            <dl class="mt-4 space-y-3 text-sm">
                <div v-if="formattedDeliveryAddress">
                    <dt class="text-stone-500">Endereço</dt>
                    <dd class="mt-0.5 font-medium text-stone-900">{{ formattedDeliveryAddress }}</dd>
                </div>
                <div>
                    <dt class="text-stone-500">Status do pedido</dt>
                    <dd class="mt-0.5 font-medium text-stone-900">{{ statusLabel(order.status) }}</dd>
                </div>
                <div v-if="order.delivery?.status">
                    <dt class="text-stone-500">Status da entrega</dt>
                    <dd class="mt-0.5 font-medium text-stone-900">
                        {{ deliveryStatusLabel(order.delivery.status) }}
                    </dd>
                </div>
                <div v-if="assignedMotoboy">
                    <dt class="text-stone-500">Entregador</dt>
                    <dd class="mt-0.5 font-medium text-stone-900">{{ assignedMotoboy.name }}</dd>
                </div>
                <div v-if="order.delivery?.motoboy_assignment_status">
                    <dt class="text-stone-500">Atribuição</dt>
                    <dd class="mt-0.5 font-medium text-stone-900">
                        {{ motoboyAssignmentLabel(order.delivery.motoboy_assignment_status) }}
                    </dd>
                </div>
                <div v-if="order.audit?.delivery_confirmed_at">
                    <dt class="text-stone-500">Confirmada em</dt>
                    <dd class="mt-0.5 font-medium text-stone-900">{{ order.audit.delivery_confirmed_at }}</dd>
                </div>
            </dl>
            <div
                v-if="assignedMotoboy"
                class="mt-4 rounded-xl border border-stone-200 bg-stone-50 p-4 text-sm"
            >
                <h3 class="font-semibold text-stone-900">Detalhes do entregador</h3>
                <p v-if="!motoboys_enabled" class="mt-1 text-xs text-stone-500">
                    Módulo de entregadores desativado — informações mantidas para consulta do pedido.
                </p>
                <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                    <div v-if="assignedMotoboy.phone">
                        <dt class="text-stone-500">Telefone</dt>
                        <dd>
                            {{ assignedMotoboy.phone }}
                            <a
                                v-if="assignedMotoboy.whatsapp_url"
                                :href="assignedMotoboy.whatsapp_url"
                                target="_blank"
                                rel="noopener"
                                class="ml-2 font-semibold text-green-700 hover:underline"
                            >
                                WhatsApp
                            </a>
                        </dd>
                    </div>
                    <div v-if="assignedMotoboy.vehicle_type">
                        <dt class="text-stone-500">Veículo</dt>
                        <dd>
                            {{ vehicleLabel(assignedMotoboy.vehicle_type) }}
                            <span v-if="assignedMotoboy.vehicle"> — {{ assignedMotoboy.vehicle }}</span>
                            <span v-if="assignedMotoboy.license_plate"> ({{ assignedMotoboy.license_plate }})</span>
                        </dd>
                    </div>
                </dl>
            </div>
            <div v-if="deliveryStatusHistories.length" class="mt-4">
                <h3 class="text-sm font-semibold text-stone-900">Histórico da entrega</h3>
                <ul class="mt-2 space-y-1.5 text-sm text-stone-700">
                    <li v-for="h in deliveryStatusHistories" :key="h.id">
                        <span class="font-medium">{{ deliveryStatusLabel(h.status) }}</span>
                        <span class="text-stone-500">
                            — {{ h.created_at_formatted ?? h.created_at }}
                            <template v-if="h.origin"> · {{ h.origin }}</template>
                        </span>
                    </li>
                </ul>
            </div>
        </template>

        <template v-else>
        <p v-if="formattedDeliveryAddress" class="mt-2 text-sm text-stone-600">
            {{ formattedDeliveryAddress }}
        </p>

        <div
            v-if="awaitingDeliveryCode && hasDeliveryCode"
            class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4"
        >
            <p class="text-sm font-medium text-amber-900">Confirmação com código do cliente</p>
            <p class="mt-1 text-xs text-amber-800">
                Peça ao cliente o código exibido no acompanhamento do pedido. Não marque como entregue sem validar o código.
            </p>
            <form class="mt-3 flex flex-wrap items-end gap-2" @submit.prevent="submitConfirmDelivery">
                <div class="min-w-[140px] flex-1">
                    <label class="mb-1 block text-xs font-medium text-stone-600">Código (6 dígitos)</label>
                    <input
                        v-model="confirmDeliveryForm.confirmation_code"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        class="admin-input font-mono text-lg tracking-widest"
                        placeholder="000000"
                        autocomplete="off"
                    />
                </div>
                <button
                    type="submit"
                    class="admin-btn-primary"
                    :disabled="confirmDeliveryForm.processing || confirmDeliveryForm.confirmation_code.length < 4"
                >
                    {{ confirmDeliveryForm.processing ? 'Confirmando...' : 'Confirmar entrega' }}
                </button>
            </form>
            <p v-if="confirmDeliveryForm.errors.confirmation_code" class="mt-2 text-sm text-red-600">
                {{ confirmDeliveryForm.errors.confirmation_code }}
            </p>
        </div>

        <form
            class="mt-4 grid gap-3"
            :class="motoboys_enabled ? 'sm:grid-cols-2' : ''"
            @submit.prevent="saveDelivery"
        >
            <select v-if="motoboys_enabled" v-model="deliveryForm.motoboy_id" class="admin-input">
                <option value="">Sem entregador</option>
                <option v-for="m in motoboys" :key="m.id" :value="m.id">{{ motoboyLabel(m) }}</option>
            </select>
            <select v-model="deliveryForm.delivery_status" class="admin-input" :class="{ 'sm:col-span-2': !motoboys_enabled }">
                <option value="pending">Aguardando</option>
                <option value="assigned">Atribuído</option>
                <option value="picked_up">Retirado</option>
                <option value="on_route">A caminho</option>
                <option value="delivered">Entregue (exige código abaixo)</option>
                <option value="failed">Falhou</option>
            </select>
            <div v-if="deliveryForm.delivery_status === 'delivered'" class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-stone-600">Código informado pelo cliente</label>
                <input
                    v-model="deliveryForm.confirmation_code"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    class="admin-input font-mono tracking-widest"
                    placeholder="000000"
                    required
                />
            </div>
            <button type="submit" class="admin-btn-primary sm:col-span-2">Atualizar entrega</button>
        </form>

        <div v-if="assignedMotoboy" class="mt-4 rounded-xl border border-stone-200 bg-stone-50 p-4 text-sm">
            <h3 class="font-semibold text-stone-900">Entregador atribuído</h3>
            <p v-if="!motoboys_enabled" class="mt-2 rounded-lg bg-stone-100 px-3 py-2 text-xs text-stone-700">
                Módulo de entregadores desativado — dados exibidos apenas para consulta deste pedido.
            </p>
            <p
                v-else-if="!assignedMotoboy.uses_app"
                class="mt-2 rounded-lg bg-amber-100 px-3 py-2 text-xs text-amber-950"
            >
                Este entregador não usa o app — imprima a comanda e atualize o status da entrega aqui no painel.
            </p>
            <p
                v-else-if="order.delivery?.motoboy_assignment_status === 'pending'"
                class="mt-2 rounded-lg bg-blue-100 px-3 py-2 text-xs text-blue-950"
            >
                Aguardando o entregador aceitar no painel web.
            </p>
            <p class="mt-1 font-medium">{{ assignedMotoboy.name }}</p>
            <dl class="mt-3 grid gap-2 sm:grid-cols-2">
                <div v-if="assignedMotoboy.phone">
                    <dt class="text-stone-500">Telefone</dt>
                    <dd>
                        {{ assignedMotoboy.phone }}
                        <a
                            v-if="assignedMotoboy.whatsapp_url"
                            :href="assignedMotoboy.whatsapp_url"
                            target="_blank"
                            rel="noopener"
                            class="ml-2 font-semibold text-green-700 hover:underline"
                        >
                            WhatsApp
                        </a>
                    </dd>
                </div>
                <div v-if="assignedMotoboy.vehicle_type">
                    <dt class="text-stone-500">Veículo</dt>
                    <dd>
                        {{ vehicleLabel(assignedMotoboy.vehicle_type) }}
                        <span v-if="assignedMotoboy.vehicle"> — {{ assignedMotoboy.vehicle }}</span>
                        <span v-if="assignedMotoboy.license_plate"> ({{ assignedMotoboy.license_plate }})</span>
                    </dd>
                </div>
                <div v-if="assignedMotoboy.pix_key">
                    <dt class="text-stone-500">Pix</dt>
                    <dd>{{ assignedMotoboy.pix_key }}</dd>
                </div>
                <div v-if="assignedMotoboy.emergency_contact_name">
                    <dt class="text-stone-500">Emergência</dt>
                    <dd>
                        {{ assignedMotoboy.emergency_contact_name }}
                        <span v-if="assignedMotoboy.emergency_contact_phone"> — {{ assignedMotoboy.emergency_contact_phone }}</span>
                    </dd>
                </div>
            </dl>
        </div>
        <div v-if="deliveryStatusHistories.length" class="mt-4">
            <h3 class="text-sm font-semibold text-stone-900">Histórico da entrega</h3>
            <ul class="mt-2 space-y-1.5 text-sm text-stone-700">
                <li v-for="h in deliveryStatusHistories" :key="h.id">
                    <span class="font-medium">{{ deliveryStatusLabel(h.status) }}</span>
                    <span class="text-stone-500">
                        — {{ h.created_at_formatted ?? h.created_at }}
                        <template v-if="h.origin"> · {{ h.origin }}</template>
                    </span>
                </li>
            </ul>
        </div>
        </template>
    </div>

    <div v-if="order.disposables_snapshot?.length" class="admin-card mt-6">
        <h2 class="font-semibold">Descartáveis do pedido</h2>
        <ul class="mt-3 space-y-1 text-sm text-stone-700">
            <li v-for="d in order.disposables_snapshot" :key="d.key">
                {{ d.label }}: <strong>{{ d.quantity ?? (d.requested ? 1 : 0) }}</strong>
            </li>
        </ul>
    </div>

    <div v-if="order.audit && (order.audit.approved_at || order.audit.cancelled_at || order.audit.rejected_at)" class="admin-card mt-6">
        <h2 class="font-semibold">Registro de decisões</h2>
        <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
            <div v-if="order.audit.approved_at">
                <dt class="text-stone-500">Aprovado em</dt>
                <dd class="font-medium text-stone-900">
                    {{ order.audit.approved_at }}
                    <span v-if="order.audit.approved_by_name" class="text-stone-600">
                        por {{ order.audit.approved_by_name }}
                    </span>
                    <span v-else class="text-stone-500"> (automático)</span>
                </dd>
            </div>
            <div v-if="order.audit.cancelled_at">
                <dt class="text-stone-500">Cancelado em</dt>
                <dd class="font-medium text-stone-900">
                    {{ order.audit.cancelled_at }}
                    <span v-if="order.audit.cancelled_by_name"> por {{ order.audit.cancelled_by_name }}</span>
                </dd>
            </div>
            <div v-if="order.audit.rejected_at">
                <dt class="text-stone-500">Recusado em</dt>
                <dd class="font-medium text-stone-900">
                    {{ order.audit.rejected_at }}
                    <span v-if="order.audit.rejected_by_name"> por {{ order.audit.rejected_by_name }}</span>
                </dd>
            </div>
            <div v-if="order.audit.delivery_confirmed_at">
                <dt class="text-stone-500">Entrega confirmada</dt>
                <dd class="font-medium text-stone-900">{{ order.audit.delivery_confirmed_at }}</dd>
            </div>
        </dl>
    </div>

    <div v-if="order.activity_logs?.length" class="admin-card mt-6">
        <h2 class="font-semibold">Log de atividades</h2>
        <ol class="mt-3 space-y-3 border-l-2 border-stone-200 pl-4">
            <li v-for="log in order.activity_logs" :key="log.id" class="text-sm">
                <p class="font-medium text-stone-900">{{ log.action_label }}</p>
                <p class="text-stone-600">{{ log.description }}</p>
                <p class="mt-0.5 text-xs text-stone-500">
                    {{ log.created_at }} · {{ log.actor_name }} ({{ log.origin }})
                </p>
            </li>
        </ol>
    </div>

    <div v-if="order.status_histories?.length" class="admin-card mt-6">
        <h2 class="font-semibold">Histórico de status</h2>
        <ul class="mt-3 space-y-2 text-sm">
            <li v-for="h in order.status_histories" :key="h.id">
                <span class="font-medium">{{ statusLabel(h.status) }}</span>
                <span class="text-stone-500">
                    — {{ h.created_at_formatted ?? h.created_at }}
                    <template v-if="h.changed_by_name"> · {{ h.changed_by_name }}</template>
                    <template v-else-if="h.origin"> · {{ h.origin }}</template>
                </span>
                <span v-if="h.notes" class="block text-stone-500">{{ h.notes }}</span>
            </li>
        </ul>
    </div>

    <Teleport to="body">
        <div
            v-if="showStatusCorrectionModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="showStatusCorrectionModal = false"
        >
            <form
                class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl"
                @submit.prevent="submitStatusCorrection"
                @click.stop
            >
                <h3 class="text-lg font-semibold text-stone-900">Corrigir status</h3>
                <p class="mt-1 text-sm text-stone-500">
                    Em pedidos de entrega, prefira a confirmação com código em vez de marcar como entregue aqui.
                </p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-stone-600">Novo status</label>
                        <select v-model="statusCorrectionForm.status" class="admin-input">
                            <option v-for="s in statusOptions" :key="s" :value="s" :disabled="s === order.status">
                                {{ statusLabel(s) }}{{ s === order.status ? ' (atual)' : '' }}
                            </option>
                        </select>
                        <p v-if="statusCorrectionForm.errors.status" class="mt-1 text-xs text-red-600">
                            {{ statusCorrectionForm.errors.status }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-stone-600">Motivo (obrigatório)</label>
                        <textarea
                            v-model="statusCorrectionForm.reason"
                            class="admin-input min-h-[72px]"
                            placeholder="Ex.: status alterado por engano..."
                            required
                        />
                        <p v-if="statusCorrectionForm.errors.reason" class="mt-1 text-xs text-red-600">
                            {{ statusCorrectionForm.errors.reason }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="admin-btn-secondary" @click="showStatusCorrectionModal = false">
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-stone-800 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-900"
                        :disabled="statusCorrectionForm.processing || statusCorrectionForm.status === order.status"
                    >
                        {{ statusCorrectionForm.processing ? 'Salvando...' : 'Aplicar' }}
                    </button>
                </div>
            </form>
        </div>

        <div
            v-if="showDeliveryEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="showDeliveryEditModal = false"
        >
            <form
                class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl"
                @submit.prevent="saveDelivery"
                @click.stop
            >
                <h3 class="text-lg font-semibold text-stone-900">Editar entrega</h3>
                <p class="mt-1 text-sm text-stone-500">
                    Use apenas para corrigir dados após a entrega. Alterações ficam registradas no histórico.
                </p>
                <div class="mt-4 grid gap-3" :class="motoboys_enabled ? 'sm:grid-cols-2' : ''">
                    <select v-if="motoboys_enabled" v-model="deliveryForm.motoboy_id" class="admin-input">
                        <option value="">Sem entregador</option>
                        <option v-for="m in motoboys" :key="m.id" :value="m.id">{{ motoboyLabel(m) }}</option>
                    </select>
                    <select
                        v-model="deliveryForm.delivery_status"
                        class="admin-input"
                        :class="{ 'sm:col-span-2': !motoboys_enabled }"
                    >
                        <option value="pending">Aguardando</option>
                        <option value="assigned">Atribuído</option>
                        <option value="picked_up">Retirado</option>
                        <option value="on_route">A caminho</option>
                        <option value="delivered">Entregue</option>
                        <option value="failed">Falhou</option>
                    </select>
                    <div v-if="deliveryForm.delivery_status === 'delivered'" class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-stone-600">
                            Código do cliente (se alterar para entregue)
                        </label>
                        <input
                            v-model="deliveryForm.confirmation_code"
                            type="text"
                            inputmode="numeric"
                            maxlength="6"
                            class="admin-input font-mono tracking-widest"
                            placeholder="Opcional se já confirmado"
                        />
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="admin-btn-secondary" @click="showDeliveryEditModal = false">
                        Cancelar
                    </button>
                    <button type="submit" class="admin-btn-primary" :disabled="deliveryForm.processing">
                        {{ deliveryForm.processing ? 'Salvando...' : 'Salvar alterações' }}
                    </button>
                </div>
            </form>
        </div>

        <div
            v-if="showRevertPaymentModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="showRevertPaymentModal = false"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl" @click.stop>
                <h3 class="text-lg font-semibold text-stone-900">Desfazer confirmação de pagamento</h3>
                <p class="mt-1 text-sm text-stone-500">
                    O pedido voltará a aparecer como pagamento pendente. Informe o motivo para o histórico.
                </p>
                <textarea
                    v-model="revertPaymentForm.reason"
                    class="admin-input mt-4 min-h-[72px]"
                    placeholder="Ex.: cliente ainda não pagou..."
                />
                <p v-if="revertPaymentForm.errors.payment" class="mt-2 text-sm text-red-600">
                    {{ revertPaymentForm.errors.payment }}
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="admin-btn-secondary" @click="showRevertPaymentModal = false">
                        Voltar
                    </button>
                    <button
                        type="button"
                        class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                        :disabled="revertPaymentForm.processing"
                        @click="submitRevertPayment"
                    >
                        {{ revertPaymentForm.processing ? 'Salvando...' : 'Desfazer pagamento' }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="showCancelModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="showCancelModal = false"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl" @click.stop>
                <h3 class="text-lg font-semibold text-stone-900">
                    {{ cancelMode === 'reject' ? 'Recusar pedido' : 'Cancelar pedido' }}
                </h3>
                <p class="mt-1 text-sm text-stone-500">Informe um motivo opcional para o cliente e o histórico.</p>
                <textarea
                    v-model="cancelForm.cancel_reason"
                    class="admin-input mt-4 min-h-[80px]"
                    placeholder="Ex.: item em falta, endereço fora da área..."
                />
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="admin-btn-secondary" @click="showCancelModal = false">Voltar</button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-white"
                        :class="cancelMode === 'reject' ? 'bg-red-600 hover:bg-red-700' : 'bg-red-600 hover:bg-red-700'"
                        :disabled="cancelForm.processing"
                        @click="submitCancel"
                    >
                        {{ cancelForm.processing ? 'Salvando...' : cancelMode === 'reject' ? 'Recusar' : 'Cancelar' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
