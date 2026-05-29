<script setup>
import DeliveryNotice from '@/Components/Platform/DeliveryNotice.vue';
import MotoboyFormFields from '@/Components/Admin/MotoboyFormFields.vue';
import MotoboyLoginManage from '@/Components/Admin/MotoboyLoginManage.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    motoboys: Array,
    branches: Array,
    formOptions: Object,
    entregadorLoginUrl: String,
    loginStats: Object,
});

const page = usePage();
const tenant = page.props.tenant;
const editingId = ref(null);
const loginManageId = ref(null);
const showCreate = ref(false);
const filter = ref('all');
const copied = ref(false);

const emptyForm = () => ({
    access_all_branches: true,
    branch_ids: [],
    name: '',
    phone: '',
    cpf: '',
    email: '',
    password: '',
    document_rg: '',
    birth_date: '',
    street: '',
    number: '',
    complement: '',
    neighborhood: '',
    city: '',
    state: '',
    postal_code: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    vehicle_type: 'motorcycle',
    vehicle: '',
    license_plate: '',
    cnh_number: '',
    cnh_category: '',
    cnh_expires_at: '',
    pix_key_type: '',
    pix_key: '',
    employment_type: 'freelancer',
    employee_code: '',
    hired_at: '',
    commission_percent: '',
    operational_status: 'available',
    max_active_deliveries: 2,
    notes: '',
    is_active: true,
    uses_app: true,
});

const form = useForm(emptyForm());
const editForm = useForm(emptyForm());

const label = (options, value) => options?.find((o) => o.value === value)?.label ?? value;

const toggleBranch = (target, id) => {
    const ids = target.branch_ids.includes(id)
        ? target.branch_ids.filter((x) => x !== id)
        : [...target.branch_ids, id];
    target.branch_ids = ids;
};

const branchSummary = (m) => {
    if (m.access_all_branches !== false) return 'Todas as filiais';
    if (m.branch_names?.length) return m.branch_names.join(', ');
    return 'Nenhuma filial';
};

const loginStatusLabel = {
    ready: { text: 'Login ativo', class: 'bg-green-100 text-green-800' },
    missing_email: { text: 'Sem e-mail', class: 'bg-amber-100 text-amber-900' },
    missing_password: { text: 'Sem senha', class: 'bg-amber-100 text-amber-900' },
    printed_only: { text: 'Só impresso', class: 'bg-stone-200 text-stone-700' },
    inactive: { text: 'Inativo', class: 'bg-red-100 text-red-800' },
};

const motoboyFromModel = (m) => ({
    access_all_branches: m.access_all_branches !== false,
    branch_ids: [...(m.branch_ids ?? [])],
    name: m.name ?? '',
    phone: m.phone ?? '',
    cpf: m.cpf ?? '',
    email: m.email ?? '',
    password: '',
    document_rg: m.document_rg ?? '',
    birth_date: m.birth_date?.slice?.(0, 10) ?? m.birth_date ?? '',
    street: m.street ?? '',
    number: m.number ?? '',
    complement: m.complement ?? '',
    neighborhood: m.neighborhood ?? '',
    city: m.city ?? '',
    state: m.state ?? '',
    postal_code: m.postal_code ?? '',
    emergency_contact_name: m.emergency_contact_name ?? '',
    emergency_contact_phone: m.emergency_contact_phone ?? '',
    vehicle_type: m.vehicle_type ?? 'motorcycle',
    vehicle: m.vehicle ?? '',
    license_plate: m.license_plate ?? '',
    cnh_number: m.cnh_number ?? '',
    cnh_category: m.cnh_category ?? '',
    cnh_expires_at: m.cnh_expires_at?.slice?.(0, 10) ?? m.cnh_expires_at ?? '',
    pix_key_type: m.pix_key_type ?? '',
    pix_key: m.pix_key ?? '',
    employment_type: m.employment_type ?? 'freelancer',
    employee_code: m.employee_code ?? '',
    hired_at: m.hired_at?.slice?.(0, 10) ?? m.hired_at ?? '',
    commission_percent: m.commission_percent ?? '',
    operational_status: m.operational_status ?? 'available',
    max_active_deliveries: m.max_active_deliveries ?? 2,
    notes: m.notes ?? '',
    is_active: m.is_active ?? true,
    uses_app: m.uses_app ?? true,
});

const filteredMotoboys = computed(() => {
    const list = props.motoboys ?? [];
    switch (filter.value) {
        case 'app':
            return list.filter((m) => m.uses_app);
        case 'printed':
            return list.filter((m) => !m.uses_app);
        case 'pending_login':
            return list.filter((m) => m.uses_app && !m.has_app_login);
        case 'inactive':
            return list.filter((m) => !m.is_active);
        case 'reports':
            return list.filter((m) => (m.open_reports_count ?? 0) > 0);
        default:
            return list;
    }
});

const copyLoginUrl = async () => {
    try {
        await navigator.clipboard.writeText(props.entregadorLoginUrl);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        window.prompt('Copie o link do painel do entregador:', props.entregadorLoginUrl);
    }
};

const submit = () =>
    form.post(route('tenant.admin.motoboys.store', { tenant: tenant.slug }), {
        onSuccess: () => {
            form.reset();
            Object.assign(form, emptyForm());
            showCreate.value = false;
        },
    });

const startEdit = (m) => {
    loginManageId.value = null;
    editingId.value = m.id;
    Object.assign(editForm, motoboyFromModel(m));
};

const openLoginManage = (m) => {
    editingId.value = null;
    loginManageId.value = m.id;
};

const submitEdit = (id) =>
    editForm.put(route('tenant.admin.motoboys.update', { tenant: tenant.slug, motoboy: id }), {
        onSuccess: () => {
            editingId.value = null;
        },
    });

const remove = (id) => {
    if (!confirm('Remover este entregador?')) return;
    router.delete(route('tenant.admin.motoboys.destroy', { tenant: tenant.slug, motoboy: id }));
};

const statusBadge = (m) => {
    if (!m.is_active) return 'bg-stone-200 text-stone-600';
    const map = {
        available: 'bg-green-100 text-green-800',
        busy: 'bg-amber-100 text-amber-800',
        offline: 'bg-stone-200 text-stone-600',
        on_break: 'bg-blue-100 text-blue-800',
    };
    return map[m.operational_status] ?? 'bg-stone-100 text-stone-700';
};

const formatRating = (m) => {
    if (!m.delivery_rating_count) return null;
    const avg = parseFloat(m.delivery_rating_average);
    if (Number.isNaN(avg)) return null;
    return { average: avg.toFixed(1), count: m.delivery_rating_count };
};

const reportsLabel = (m) => {
    const open = m.open_reports_count ?? 0;
    if (open === 0) return null;
    return open === 1 ? '1 denúncia pendente' : `${open} denúncias pendentes`;
};
</script>

<template>
    <Head title="Entregadores" />

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="admin-page-title">Entregadores</h1>
            <p class="mt-1 text-sm text-stone-500">
                Cadastros do restaurante, filiais atendidas e logins do painel web do entregador em um só lugar.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="admin-btn-primary" @click="showCreate = !showCreate">
                {{ showCreate ? 'Fechar cadastro' : 'Novo entregador' }}
            </button>
        </div>
    </div>

    <DeliveryNotice variant="motoboy_admin" class="mt-6 max-w-3xl" />

    <!-- Link do painel web do entregador -->
    <div class="admin-card mt-6">
        <h2 class="font-semibold text-stone-900">Link do painel do entregador</h2>
        <p class="mt-1 text-sm text-stone-500">Envie este endereço para quem atualiza entregas pelo celular (site, não app da loja).</p>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <code class="min-w-0 flex-1 break-all rounded-lg bg-stone-100 px-3 py-2 text-sm text-stone-800">
                {{ entregadorLoginUrl }}
            </code>
            <button type="button" class="admin-btn-secondary shrink-0 text-sm" @click="copyLoginUrl">
                {{ copied ? 'Copiado!' : 'Copiar link' }}
            </button>
            <a :href="entregadorLoginUrl" target="_blank" rel="noopener" class="admin-btn-primary shrink-0 text-sm">
                Abrir painel
            </a>
        </div>
        <dl v-if="loginStats" class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
            <div class="rounded-lg bg-stone-50 px-3 py-2">
                <dt class="text-stone-500">Total</dt>
                <dd class="text-lg font-semibold text-stone-900">{{ loginStats.total }}</dd>
            </div>
            <div class="rounded-lg bg-violet-50 px-3 py-2">
                <dt class="text-violet-700">Com painel web</dt>
                <dd class="text-lg font-semibold text-violet-950">{{ loginStats.with_app }}</dd>
            </div>
            <div class="rounded-lg bg-green-50 px-3 py-2">
                <dt class="text-green-700">Login pronto</dt>
                <dd class="text-lg font-semibold text-green-950">{{ loginStats.with_login }}</dd>
            </div>
            <div class="rounded-lg bg-amber-50 px-3 py-2">
                <dt class="text-amber-800">Pendente login</dt>
                <dd class="text-lg font-semibold text-amber-950">{{ loginStats.pending_login }}</dd>
            </div>
        </dl>
    </div>

    <!-- Filtros -->
    <div class="mt-4 flex flex-wrap gap-2">
        <button
            v-for="f in [
                { id: 'all', label: 'Todos' },
                { id: 'app', label: 'Com painel web' },
                { id: 'printed', label: 'Só impresso' },
                { id: 'pending_login', label: 'Login pendente' },
                { id: 'inactive', label: 'Inativos' },
                { id: 'reports', label: 'Com denúncia' },
            ]"
            :key="f.id"
            type="button"
            class="rounded-full px-3 py-1 text-sm font-medium transition"
            :class="filter === f.id ? 'bg-orange-500 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'"
            @click="filter = f.id"
        >
            {{ f.label }}
        </button>
    </div>

    <form v-if="showCreate" class="admin-card mt-6 space-y-4" @submit.prevent="submit">
        <h2 class="text-base font-semibold text-stone-900">Cadastrar entregador</h2>
        <MotoboyFormFields
            :form="form"
            :branches="branches"
            :form-options="formOptions"
            @toggle-branch="toggleBranch(form, $event)"
        />
        <div class="flex gap-2 border-t border-stone-100 pt-4">
            <button type="button" class="admin-btn-secondary" @click="showCreate = false">Cancelar</button>
            <button type="submit" class="admin-btn-primary" :disabled="form.processing">Salvar cadastro</button>
        </div>
    </form>

    <form v-if="editingId" class="admin-card mt-6 space-y-4" @submit.prevent="submitEdit(editingId)">
        <h2 class="text-base font-semibold text-stone-900">Editar entregador</h2>
        <MotoboyFormFields
            :form="editForm"
            :branches="branches"
            :form-options="formOptions"
            @toggle-branch="toggleBranch(editForm, $event)"
        />
        <div class="flex gap-2 border-t border-stone-100 pt-4">
            <button type="button" class="admin-btn-secondary" @click="editingId = null">Cancelar</button>
            <button type="submit" class="admin-btn-primary" :disabled="editForm.processing">Atualizar cadastro</button>
        </div>
    </form>

    <!-- Lista -->
    <div class="mt-6 space-y-4">
        <article
            v-for="m in filteredMotoboys"
            :key="m.id"
            class="admin-card"
            :class="m.open_reports_count > 0 ? 'ring-2 ring-red-200' : ''"
        >
            <div
                v-if="m.open_reports_count > 0"
                class="-mx-5 -mt-5 mb-4 flex flex-wrap items-center justify-between gap-2 rounded-t-2xl border-b border-red-200 bg-red-50 px-5 py-2.5"
            >
                <p class="text-sm font-semibold text-red-900">
                    ⚠ {{ reportsLabel(m) }}
                </p>
                <Link
                    :href="route('tenant.admin.motoboy-reports.index', { tenant: tenant.slug })"
                    class="text-xs font-semibold text-red-800 underline hover:text-red-950"
                >
                    Ver denúncias
                </Link>
            </div>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-stone-900">{{ m.name }}</h2>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusBadge(m)">
                            {{ label(formOptions.operational_statuses, m.operational_status) }}
                        </span>
                        <span
                            v-if="formatRating(m)"
                            class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-900"
                            :title="`${formatRating(m).count} avaliação(ões) de entrega`"
                        >
                            ★ {{ formatRating(m).average }}
                            <span class="font-normal text-amber-800/80">({{ formatRating(m).count }})</span>
                        </span>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="loginStatusLabel[m.login_status]?.class ?? 'bg-stone-100 text-stone-700'"
                        >
                            {{ loginStatusLabel[m.login_status]?.text ?? m.login_status }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-stone-600">
                        {{ m.phone }}
                        <span v-if="m.employee_code"> · {{ m.employee_code }}</span>
                        <span> · {{ branchSummary(m) }}</span>
                    </p>
                    <p v-if="m.uses_app && m.email" class="mt-1 text-sm text-stone-500">
                        Login: <span class="font-medium text-stone-800">{{ m.email }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="admin-btn-primary text-sm" @click="openLoginManage(m)">
                        Acesso / login
                    </button>
                    <button type="button" class="admin-btn-secondary text-sm" @click="startEdit(m)">
                        Cadastro completo
                    </button>
                    <a
                        v-if="m.whatsapp_url"
                        :href="m.whatsapp_url"
                        target="_blank"
                        rel="noopener"
                        class="admin-btn-secondary text-sm"
                    >
                        WhatsApp
                    </a>
                    <button type="button" class="text-sm text-red-600 hover:underline" @click="remove(m.id)">Excluir</button>
                </div>
            </div>

            <MotoboyLoginManage
                v-if="loginManageId === m.id"
                :motoboy="m"
                :tenant-slug="tenant.slug"
                @close="loginManageId = null"
                @saved="loginManageId = null"
            />

            <dl v-if="editingId !== m.id && loginManageId !== m.id" class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-stone-500">Veículo</dt>
                    <dd class="font-medium text-stone-800">
                        {{ label(formOptions.vehicle_types, m.vehicle_type) }}
                        <span v-if="m.vehicle"> — {{ m.vehicle }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-stone-500">Entregas ativas</dt>
                    <dd class="font-medium text-stone-800">
                        {{ m.active_deliveries_count }} / {{ m.max_active_deliveries }}
                    </dd>
                </div>
                <div v-if="m.pix_key">
                    <dt class="text-stone-500">Pix</dt>
                    <dd class="font-medium text-stone-800">{{ m.pix_key }}</dd>
                </div>
                <div v-if="formatRating(m)">
                    <dt class="text-stone-500">Nota média (entrega)</dt>
                    <dd class="font-medium text-stone-800">
                        ★ {{ formatRating(m).average }}
                        <span class="text-stone-500">· {{ formatRating(m).count }} avaliação(ões)</span>
                    </dd>
                </div>
                <div v-if="m.total_reports_count > 0">
                    <dt class="text-stone-500">Denúncias</dt>
                    <dd class="font-medium" :class="m.open_reports_count > 0 ? 'text-red-700' : 'text-stone-800'">
                        {{ m.open_reports_count }} pendente(s)
                        <span v-if="m.total_reports_count > m.open_reports_count" class="text-stone-500">
                            · {{ m.total_reports_count }} no total
                        </span>
                    </dd>
                </div>
            </dl>
        </article>

        <p v-if="!filteredMotoboys.length" class="admin-card text-center text-stone-500">
            Nenhum entregador neste filtro.
        </p>
    </div>
</template>
