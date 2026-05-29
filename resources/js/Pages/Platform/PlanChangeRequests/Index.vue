<script setup>
import PlatformLayout from '@/Layouts/PlatformLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: PlatformLayout });

const props = defineProps({
    requests: Object,
    filters: Object,
    statusLabels: Object,
    pendingCount: Number,
});

const page = usePage();
const platformT = () => page.props.platformTranslations?.plan_change_requests ?? {};

const reviewingId = ref(null);
const reviewForm = useForm({ admin_notes: '' });

const apply = (patch) =>
    router.get(route('platform.plan-change-requests.index'), { ...props.filters, ...patch }, { preserveState: true });

const formatDate = (iso) =>
    iso
        ? new Date(iso).toLocaleString('pt-BR', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '—';

const formatPrice = (value) =>
    parseFloat(value ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const statusClass = (status) => {
    if (status === 'pending') return 'bg-amber-100 text-amber-900';
    if (status === 'approved') return 'bg-green-100 text-green-900';
    return 'bg-red-100 text-red-900';
};

const startReview = (id) => {
    reviewingId.value = id;
    reviewForm.reset();
    reviewForm.clearErrors();
};

const cancelReview = () => {
    reviewingId.value = null;
    reviewForm.reset();
};

const approve = (id) => {
    reviewForm.post(route('platform.plan-change-requests.approve', id), {
        preserveScroll: true,
        onSuccess: () => cancelReview(),
    });
};

const reject = (id) => {
    reviewForm.post(route('platform.plan-change-requests.reject', id), {
        preserveScroll: true,
        onSuccess: () => cancelReview(),
    });
};
</script>

<template>
    <Head :title="platformT().page_title ?? 'Migrações de plano'" />

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                {{ platformT().page_title ?? 'Migrações de plano' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ platformT().intro ?? 'Solicitações dos restaurantes para alterar o plano contratado.' }}
            </p>
        </div>
        <span
            v-if="pendingCount > 0"
            class="rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-900"
        >
            {{ pendingCount }} pendente{{ pendingCount === 1 ? '' : 's' }}
        </span>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <input
            type="search"
            class="platform-input max-w-xs"
            placeholder="Restaurante ou slug…"
            :value="filters?.q ?? ''"
            @change="apply({ q: $event.target.value || undefined })"
        />
        <select
            class="platform-input w-auto"
            :value="filters?.status ?? ''"
            @change="apply({ status: $event.target.value || undefined })"
        >
            <option value="">Todos os status</option>
            <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
        </select>
    </div>

    <div class="mt-6 space-y-4">
        <article
            v-for="req in requests.data"
            :key="req.id"
            class="platform-card"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <Link
                        :href="route('platform.tenants.show', req.tenant.id)"
                        class="text-lg font-semibold text-indigo-600 hover:underline"
                    >
                        {{ req.tenant.name }}
                    </Link>
                    <p class="text-sm text-slate-500">/{{ req.tenant.slug }}</p>
                    <p class="mt-2 text-sm text-slate-700">
                        <span class="font-medium">{{ req.current_plan?.name ?? '—' }}</span>
                        ({{ formatPrice(req.current_plan?.price_monthly) }})
                        →
                        <span class="font-medium text-indigo-700">{{ req.requested_plan?.name }}</span>
                        ({{ formatPrice(req.requested_plan?.price_monthly) }})
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        Solicitado por {{ req.requested_by_user?.name ?? '—' }} em {{ formatDate(req.created_at) }}
                    </p>
                    <p v-if="req.message" class="mt-2 text-sm text-slate-600">{{ req.message }}</p>
                    <p v-if="req.admin_notes && req.status !== 'pending'" class="mt-2 text-sm text-slate-600">
                        Observações: {{ req.admin_notes }}
                    </p>
                    <p v-if="req.reviewed_by_user" class="mt-1 text-xs text-slate-400">
                        Analisado por {{ req.reviewed_by_user.name }} em {{ formatDate(req.reviewed_at) }}
                    </p>
                </div>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(req.status)">
                    {{ statusLabels[req.status] ?? req.status }}
                </span>
            </div>

            <div v-if="req.status === 'pending'" class="mt-4 border-t border-slate-100 pt-4">
                <template v-if="reviewingId === req.id">
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        {{ platformT().admin_notes ?? 'Observações (opcional)' }}
                    </label>
                    <textarea v-model="reviewForm.admin_notes" rows="2" class="platform-input w-full max-w-lg" />
                    <p v-if="reviewForm.errors.plan" class="mt-1 text-sm text-red-600">{{ reviewForm.errors.plan }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="platform-btn-primary"
                            :disabled="reviewForm.processing"
                            @click="approve(req.id)"
                        >
                            {{ platformT().approve ?? 'Aprovar' }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50"
                            :disabled="reviewForm.processing"
                            @click="reject(req.id)"
                        >
                            {{ platformT().reject ?? 'Recusar' }}
                        </button>
                        <button type="button" class="platform-btn-secondary" @click="cancelReview">Cancelar</button>
                    </div>
                </template>
                <button
                    v-else
                    type="button"
                    class="platform-btn-secondary text-sm"
                    @click="startReview(req.id)"
                >
                    Analisar solicitação
                </button>
            </div>
        </article>

        <p v-if="!requests.data?.length" class="text-sm text-slate-500">Nenhuma solicitação encontrada.</p>
    </div>
</template>
