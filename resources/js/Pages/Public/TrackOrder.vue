<script setup>
import ChatWidget from '@/Components/Public/ChatWidget.vue';
import StarRatingInput from '@/Components/StarRatingInput.vue';
import SeoHead from '@/Components/SeoHead.vue';
import { formatDeliveryAddress } from '@/composables/formatDeliveryAddress';
import { formatEstimateMinutes } from '@/composables/useOrderDeliveryEstimate';
import { markChatPurchasedLocal } from '@/composables/useChatEligibility';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    order: Object,
    tenantSlug: String,
    seo: Object,
    chatAvailable: { type: Boolean, default: false },
    chatGuestProfile: { type: Object, default: null },
    guestAccess: { type: Object, default: null },
});

const page = usePage();
const order = ref({ ...props.order });

const showPixPayment = computed(
    () => order.value.pix_payment && order.value.payment_status !== 'paid',
);

const deliveryAddressDisplay = computed(() => {
    const current = order.value;
    if (current?.type !== 'delivery') {
        return null;
    }

    return (
        current.delivery_address_formatted ||
        formatDeliveryAddress(current.delivery_address) ||
        null
    );
});

const ratingForm = useForm({
    rating: 5,
    comment: '',
    restaurant_rating: 5,
    restaurant_comment: '',
    delivery_rating: 5,
    delivery_comment: '',
});

const deliveryReportForm = useForm({ message: '' });
const orderReportForm = useForm({ message: '' });
const showDeliveryIssueForm = ref(false);
const showOrderIssueForm = ref(false);

const submitDeliveryReport = () =>
    deliveryReportForm.post(
        route('tenant.track.report-motoboy', { tenant: props.tenantSlug, order_number: props.order.order_number }),
        {
            onSuccess: () => {
                deliveryReportForm.reset();
                showDeliveryIssueForm.value = false;
            },
        },
    );

const submitOrderReport = () =>
    orderReportForm.post(
        route('tenant.track.report-order', { tenant: props.tenantSlug, order_number: props.order.order_number }),
        {
            onSuccess: () => {
                orderReportForm.reset();
                showOrderIssueForm.value = false;
            },
        },
    );

const submitRating = () =>
    ratingForm.post(
        route('tenant.track.rate', { tenant: props.tenantSlug, order_number: props.order.order_number }),
        {
            onSuccess: () => {
                order.value.can_rate = false;
                order.value.rating = {
                    order_rating: ratingForm.rating,
                    order_comment: ratingForm.comment,
                    restaurant_rating: ratingForm.restaurant_rating,
                    restaurant_comment: ratingForm.restaurant_comment,
                    delivery_rating: order.value.is_delivery ? ratingForm.delivery_rating : null,
                    delivery_comment: order.value.is_delivery ? ratingForm.delivery_comment : null,
                };
            },
        },
    );

const t = (key) => page.props.translations?.[key] ?? key;
const statusLabel = (status) => t(`order.status.${status}`) || status;
const formatPrice = (value) =>
    parseFloat(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const formatDateTime = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const amountPositive = (value) => parseFloat(value) > 0;

const accessCode = () =>
    props.guestAccess?.code ?? page.props.flash?.guest_access_code ?? null;

const maskedPhone = () => {
    const digits = (props.guestAccess?.guest_phone ?? '').replace(/\D/g, '');
    if (digits.length < 4) return '';
    return `••••${digits.slice(-4)}`;
};

const copyText = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        /* ignore */
    }
};

let pollTimer;

const pollStatus = async () => {
    try {
        const url = route('tenant.track.status', {
            tenant: props.tenantSlug,
            order_number: props.order.order_number,
        });
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        order.value = {
            ...order.value,
            status: data.status,
            status_histories: data.status_histories,
            show_delivery_code: data.show_delivery_code,
            delivery_confirmation_code: data.delivery_confirmation_code,
            payment_status: data.payment_status,
            payment_status_label: data.payment_status_label,
            pix_payment: data.pix_payment,
        };
    } catch {
        /* ignore network errors during poll */
    }
};

const isRecentOrderForChat = (iso) => {
    if (!iso) return false;
    const at = new Date(iso);
    if (Number.isNaN(at.getTime())) return false;
    const cutoff = Date.now() - 15 * 24 * 60 * 60 * 1000;
    return at.getTime() >= cutoff;
};

onMounted(() => {
    if (props.order?.branch?.slug && isRecentOrderForChat(props.order.created_at)) {
        markChatPurchasedLocal(props.tenantSlug, props.order.branch.slug, props.order.created_at);
    }
    pollTimer = setInterval(pollStatus, 10000);
});

onUnmounted(() => clearInterval(pollTimer));
</script>

<template>
    <SeoHead :seo="seo" />

    <div v-if="page.props.flash?.success" class="mb-6 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ page.props.flash.success }}
    </div>

    <div
        v-if="showPixPayment"
        class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"
    >
        <p class="font-semibold">{{ t('payment.pix_online') }}</p>
        <p class="mt-1">{{ order.pix_payment.instructions || t('payment.pix_instructions') }}</p>
        <p class="mt-2 text-lg font-bold">{{ formatPrice(order.pix_payment.amount) }}</p>
        <p v-if="order.pix_payment.beneficiary" class="text-xs">Beneficiário: {{ order.pix_payment.beneficiary }}</p>
        <code class="mt-2 block break-all rounded-lg bg-white p-2 text-xs">{{ order.pix_payment.copy_paste }}</code>
        <button
            type="button"
            class="mt-2 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white"
            @click="navigator.clipboard?.writeText(order.pix_payment.copy_paste)"
        >
            {{ t('payment.pix_copy') }}
        </button>
        <p class="mt-2 text-xs text-emerald-800">{{ t('payment.pending') }}</p>
    </div>

    <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
        <div class="bg-stone-900 px-6 py-8 text-center text-white">
            <p class="text-xs font-medium uppercase tracking-widest text-white/60">Acompanhar pedido</p>
            <h1 class="mt-2 font-display text-2xl font-semibold">{{ order.order_number }}</h1>
            <span
                class="mt-4 inline-block rounded-full px-4 py-1.5 text-sm font-semibold"
                style="background-color: color-mix(in srgb, var(--menu-primary) 90%, white); color: white"
            >
                {{ statusLabel(order.status) }}
            </span>
            <p class="mt-3 text-xs text-white/50">{{ t('order.track.status_hint') }}</p>
        </div>

        <div
            v-if="order.show_delivery_code && order.delivery_confirmation_code"
            class="border-b border-amber-200 bg-amber-50 px-6 py-6 text-center"
        >
            <p class="text-xs font-semibold uppercase tracking-widest text-amber-800">
                {{ t('order.delivery_code.title') }}
            </p>
            <p
                class="mt-2 font-mono text-4xl font-bold tracking-[0.35em] text-amber-950"
                aria-label="Código de entrega"
            >
                {{ order.delivery_confirmation_code }}
            </p>
            <p class="mt-3 text-sm text-amber-900/80">
                {{ t('order.delivery_code.hint') }}
            </p>
        </div>

        <div
            v-else-if="order.type === 'delivery' && order.status === 'out_for_delivery'"
            class="border-b border-stone-100 bg-stone-50 px-6 py-4 text-center text-sm text-stone-600"
        >
            {{ t('order.delivery_code.pending') }}
        </div>

        <div
            v-if="order.type === 'delivery'"
            class="border-b border-stone-100 px-6 py-5"
        >
            <h2 class="font-display text-base font-semibold text-stone-900">
                {{ t('order.track.delivery_address') }}
            </h2>
            <p v-if="deliveryAddressDisplay" class="mt-2 text-sm leading-relaxed text-stone-800">
                {{ deliveryAddressDisplay }}
            </p>
            <p v-else class="mt-2 text-sm text-stone-500">
                {{ t('order.track.delivery_address_missing') }}
            </p>
        </div>

        <div class="border-b border-stone-100 bg-stone-50/60 px-6 py-5">
            <h2 class="font-display text-base font-semibold text-stone-900">{{ t('order.track.details_title') }}</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div v-if="order.branch?.name" class="flex justify-between gap-4">
                    <dt class="text-stone-500">{{ t('order.track.branch') }}</dt>
                    <dd class="text-right font-medium text-stone-900">{{ order.branch.name }}</dd>
                </div>
                <div v-if="order.created_at" class="flex justify-between gap-4">
                    <dt class="text-stone-500">{{ t('order.track.placed_at') }}</dt>
                    <dd class="text-right text-stone-800">{{ formatDateTime(order.created_at) }}</dd>
                </div>
                <div v-if="order.scheduled_for" class="flex justify-between gap-4">
                    <dt class="text-stone-500">{{ t('order.track.scheduled_for') }}</dt>
                    <dd class="text-right font-medium text-stone-900">{{ formatDateTime(order.scheduled_for) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-stone-500">{{ t('order.track.order_type') }}</dt>
                    <dd class="text-right font-medium text-stone-900">{{ order.type_label }}</dd>
                </div>
                <div
                    v-if="order.estimated_minutes && !['delivered', 'cancelled', 'rejected'].includes(order.status)"
                    class="flex justify-between gap-4"
                >
                    <dt class="text-stone-500">{{ t('order.track.estimated_time') }}</dt>
                    <dd class="text-right font-medium text-stone-900">
                        {{ formatEstimateMinutes(order.estimated_minutes) }}
                    </dd>
                </div>
                <div class="border-t border-stone-200/80 pt-3">
                    <dt class="text-stone-500">{{ t('order.track.payment') }}</dt>
                    <dd class="mt-1 font-medium text-stone-900">{{ order.payment_label }}</dd>
                    <dd class="mt-0.5 text-xs text-stone-600">{{ order.payment_status_label }}</dd>
                </div>
                <div v-if="order.notes" class="border-t border-stone-200/80 pt-3">
                    <dt class="text-stone-500">{{ t('order.track.notes') }}</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-stone-800">{{ order.notes }}</dd>
                </div>
            </dl>
        </div>

        <div class="p-6">
            <h2 class="font-display text-lg font-semibold">Itens</h2>
            <ul class="mt-4 divide-y divide-stone-100">
                <li v-for="(item, i) in order.items" :key="i" class="py-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-stone-700">{{ item.quantity }}× {{ item.name }}</span>
                        <span class="shrink-0 font-medium">{{ formatPrice(item.total_price) }}</span>
                    </div>
                    <ul v-if="item.variations?.length" class="mt-1.5 space-y-0.5 text-xs text-stone-500">
                        <li v-for="(v, vi) in item.variations" :key="vi">
                            {{ v.option_name }}{{ v.quantity > 1 ? ` ×${v.quantity}` : '' }}
                        </li>
                    </ul>
                    <p v-if="item.notes" class="mt-1 text-xs italic text-stone-500">{{ item.notes }}</p>
                </li>
            </ul>
            <dl class="mt-4 space-y-2 border-t border-stone-100 pt-4 text-sm">
                <div class="flex justify-between text-stone-600">
                    <dt>{{ t('order.track.subtotal') }}</dt>
                    <dd>{{ formatPrice(order.subtotal) }}</dd>
                </div>
                <div v-if="amountPositive(order.delivery_fee)" class="flex justify-between text-stone-600">
                    <dt>{{ t('order.track.delivery_fee') }}</dt>
                    <dd>{{ formatPrice(order.delivery_fee) }}</dd>
                </div>
                <div v-if="amountPositive(order.packaging_fee)" class="flex justify-between text-stone-600">
                    <dt>{{ t('order.track.packaging_fee') }}</dt>
                    <dd>{{ formatPrice(order.packaging_fee) }}</dd>
                </div>
                <div v-if="amountPositive(order.service_fee)" class="flex justify-between text-stone-600">
                    <dt>{{ t('order.track.service_fee') }}</dt>
                    <dd>{{ formatPrice(order.service_fee) }}</dd>
                </div>
                <div v-if="amountPositive(order.discount_amount)" class="flex justify-between text-emerald-700">
                    <dt>{{ t('order.track.discount') }}</dt>
                    <dd>− {{ formatPrice(order.discount_amount) }}</dd>
                </div>
                <div v-if="amountPositive(order.tip_amount)" class="flex justify-between text-stone-600">
                    <dt>{{ t('order.track.tip') }}</dt>
                    <dd>{{ formatPrice(order.tip_amount) }}</dd>
                </div>
                <div class="flex justify-between border-t border-stone-100 pt-2 text-base font-semibold text-stone-900">
                    <dt>{{ t('order.track.total') }}</dt>
                    <dd class="font-display" style="color: var(--menu-primary)">{{ formatPrice(order.total) }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section v-if="order.can_rate" class="mt-6 rounded-2xl border border-stone-200 bg-white p-6">
        <h2 class="font-display font-semibold">Como foi sua experiência?</h2>
        <p class="mt-1 text-sm text-stone-500">Sua opinião ajuda o restaurante e a equipe a melhorar.</p>
        <form class="mt-5 space-y-5" @submit.prevent="submitRating">
            <StarRatingInput v-model="ratingForm.restaurant_rating" label="Restaurante" />
            <textarea
                v-model="ratingForm.restaurant_comment"
                class="menu-input w-full text-sm"
                rows="2"
                placeholder="Comentário sobre o restaurante (opcional)"
            />

            <StarRatingInput v-model="ratingForm.rating" label="Pedido (comida e embalagem)" />
            <textarea
                v-model="ratingForm.comment"
                class="menu-input w-full text-sm"
                rows="2"
                placeholder="Comentário sobre o pedido (opcional)"
            />

            <template v-if="order.is_delivery">
                <StarRatingInput v-model="ratingForm.delivery_rating" label="Entrega" />
                <textarea
                    v-model="ratingForm.delivery_comment"
                    class="menu-input w-full text-sm"
                    rows="2"
                    placeholder="Comentário sobre a entrega (opcional)"
                />
            </template>

            <button type="submit" class="menu-btn-primary w-full" :disabled="ratingForm.processing">
                Enviar avaliações
            </button>
        </form>
    </section>

    <section
        v-if="order.can_report_motoboy || order.can_report_order"
        class="mt-6 rounded-2xl border border-stone-200 bg-white p-4"
    >
        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
            <button
                v-if="order.can_report_motoboy && !showDeliveryIssueForm"
                type="button"
                class="text-left text-sm font-medium text-stone-700 underline-offset-2 hover:text-stone-900 hover:underline"
                @click="showDeliveryIssueForm = true; showOrderIssueForm = false"
            >
                {{ t('order.track.issue_delivery') }}
            </button>
            <button
                v-if="order.can_report_order && !showOrderIssueForm"
                type="button"
                class="text-left text-sm font-medium text-stone-700 underline-offset-2 hover:text-stone-900 hover:underline"
                @click="showOrderIssueForm = true; showDeliveryIssueForm = false"
            >
                {{ t('order.track.issue_order') }}
            </button>
        </div>

        <div v-if="showDeliveryIssueForm" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4">
            <div class="flex items-start justify-between gap-2">
                <h2 class="text-sm font-semibold text-red-900">{{ t('order.track.issue_delivery_title') }}</h2>
                <button
                    type="button"
                    class="shrink-0 text-xs text-red-800/80 hover:text-red-900"
                    @click="showDeliveryIssueForm = false"
                >
                    {{ t('order.track.issue_cancel') }}
                </button>
            </div>
            <p class="mt-1 text-xs text-red-800/90">{{ t('order.track.issue_delivery_hint') }}</p>
            <p v-if="order.motoboy_name" class="mt-2 text-sm text-red-800">
                {{ t('order.track.issue_delivery_motoboy').replace(':name', order.motoboy_name) }}
            </p>
            <form class="mt-3 space-y-2" @submit.prevent="submitDeliveryReport">
                <textarea
                    v-model="deliveryReportForm.message"
                    class="menu-input w-full !bg-white"
                    rows="3"
                    :placeholder="t('order.track.issue_placeholder')"
                    required
                />
                <button
                    type="submit"
                    class="w-full rounded-xl border border-red-300 bg-white py-2 text-sm font-semibold text-red-800"
                    :disabled="deliveryReportForm.processing"
                >
                    {{ t('order.track.issue_delivery_submit') }}
                </button>
            </form>
        </div>

        <div v-if="showOrderIssueForm" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <div class="flex items-start justify-between gap-2">
                <h2 class="text-sm font-semibold text-amber-950">{{ t('order.track.issue_order_title') }}</h2>
                <button
                    type="button"
                    class="shrink-0 text-xs text-amber-900/80 hover:text-amber-950"
                    @click="showOrderIssueForm = false"
                >
                    {{ t('order.track.issue_cancel') }}
                </button>
            </div>
            <p class="mt-1 text-xs text-amber-900/90">{{ t('order.track.issue_order_hint') }}</p>
            <form class="mt-3 space-y-2" @submit.prevent="submitOrderReport">
                <textarea
                    v-model="orderReportForm.message"
                    class="menu-input w-full !bg-white"
                    rows="3"
                    :placeholder="t('order.track.issue_placeholder')"
                    required
                />
                <button
                    type="submit"
                    class="w-full rounded-xl border border-amber-300 bg-white py-2 text-sm font-semibold text-amber-950"
                    :disabled="orderReportForm.processing"
                >
                    {{ t('order.track.issue_order_submit') }}
                </button>
            </form>
        </div>
    </section>

    <section v-else-if="order.rating" class="mt-6 space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-6 text-sm text-emerald-900">
        <p class="font-semibold">Obrigado pelas avaliações!</p>
        <p v-if="order.rating.restaurant_rating">Restaurante: {{ '★'.repeat(order.rating.restaurant_rating) }}</p>
        <p v-if="order.rating.order_rating">Pedido: {{ '★'.repeat(order.rating.order_rating) }}</p>
        <p v-if="order.rating.delivery_rating">Entrega: {{ '★'.repeat(order.rating.delivery_rating) }}</p>
    </section>

    <section v-if="order.status_histories?.length" class="mt-6 rounded-2xl border border-stone-200 bg-white p-6">
        <h2 class="font-display font-semibold">Histórico</h2>
        <ol class="mt-4 space-y-3 border-l-2 border-stone-200 pl-4">
            <li v-for="(h, i) in order.status_histories" :key="i" class="text-sm">
                <span class="font-medium text-stone-900">{{ statusLabel(h.status) }}</span>
                <time
                    v-if="h.created_at"
                    class="mt-0.5 block text-xs text-stone-500"
                    :datetime="h.created_at"
                >
                    {{ formatDateTime(h.created_at) }}
                </time>
            </li>
        </ol>
    </section>

    <details
        v-if="accessCode()"
        class="group mt-6 rounded-xl border border-stone-200/70 bg-stone-50/80 text-stone-600 open:bg-stone-50"
    >
        <summary
            class="cursor-pointer list-none px-3 py-2.5 text-xs text-stone-500 marker:content-none [&::-webkit-details-marker]:hidden"
        >
            <span class="flex flex-wrap items-center justify-between gap-2">
                <span>{{ t('order.access.code_label') }}</span>
                <span
                    class="font-mono text-sm font-medium tracking-widest text-stone-700 group-open:hidden"
                    aria-hidden="true"
                >
                    {{ accessCode() }}
                </span>
                <span class="text-[10px] text-stone-400 group-open:hidden">ver mais</span>
                <span class="hidden text-[10px] text-stone-400 group-open:inline">recolher</span>
            </span>
        </summary>
        <div class="border-t border-stone-200/60 px-3 pb-3 pt-2">
            <p class="text-[11px] font-medium text-stone-500">
                {{ t('order.access.save_title') }}
            </p>
            <p
                class="mt-1 font-mono text-lg font-semibold tracking-[0.25em] text-stone-700"
                aria-label="Código de acesso ao pedido"
            >
                {{ accessCode() }}
            </p>
            <p class="mt-1.5 text-[11px] leading-relaxed text-stone-500">
                {{ t('order.access.save_hint') }}
            </p>
            <p v-if="guestAccess?.email_sent" class="mt-1 text-[11px] text-stone-400">
                {{ t('order.access.sent_email') }}
            </p>
            <p v-else-if="maskedPhone()" class="mt-1 text-[11px] text-stone-400">
                {{ t('order.access.phone_hint').replace(':phone', maskedPhone()) }}
            </p>
            <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px]">
                <button
                    type="button"
                    class="text-stone-600 underline decoration-stone-300 underline-offset-2 hover:text-stone-800"
                    @click="copyText(accessCode())"
                >
                    {{ t('order.access.copy_code') }}
                </button>
                <button
                    v-if="guestAccess?.track_url"
                    type="button"
                    class="text-stone-600 underline decoration-stone-300 underline-offset-2 hover:text-stone-800"
                    @click="copyText(guestAccess.track_url)"
                >
                    {{ t('order.access.copy_link') }}
                </button>
                <a
                    v-if="guestAccess?.lookup_url"
                    :href="guestAccess.lookup_url"
                    class="text-stone-500 underline decoration-stone-300 underline-offset-2 hover:text-stone-700"
                >
                    {{ t('order.access.lookup_link') }}
                </a>
            </div>
        </div>
    </details>

    <Link
        :href="route('tenant.home', { tenant: tenantSlug })"
        class="mt-8 block text-center text-sm font-medium hover:underline"
        style="color: var(--menu-primary)"
    >
        ← Voltar
    </Link>

    <ChatWidget
        v-if="chatAvailable && order.branch?.slug"
        enabled
        :tenant-slug="tenantSlug"
        :branch-slug="order.branch.slug"
        :initial-guest-name="chatGuestProfile?.name ?? order.guest_name ?? ''"
        :initial-guest-phone="chatGuestProfile?.phone ?? ''"
    />
</template>
