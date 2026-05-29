<script setup>
import AdminListSearch from '@/Components/Admin/AdminListSearch.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AdminLayout });

defineProps({ requests: Array, filters: Object });

const page = usePage();
const tenant = page.props.tenant;
const { can } = usePermissions();
const expandedId = ref(null);

const typeLabel = (type) =>
    ({ help: 'Ajuda', complaint: 'Reclamação', return: 'Devolução' }[type] ?? type);

const statusLabel = (status) =>
    ({ open: 'Aberta', in_progress: 'Em andamento', closed: 'Fechada' }[status] ?? status);

const noteForm = useForm({ admin_notes: '', status: '' });

const openRequest = (r) => {
    expandedId.value = expandedId.value === r.id ? null : r.id;
    noteForm.admin_notes = r.admin_notes ?? '';
    noteForm.status = r.status;
};

const save = (id) =>
    noteForm.patch(route('tenant.admin.requests.update', { tenant: tenant.slug, supportRequest: id }), {
        preserveScroll: true,
        onSuccess: () => {
            expandedId.value = null;
        },
    });

const processReturn = (id) => {
    if (!confirm('Processar devolução? O pedido será cancelado, estoque restaurado e pagamento estornado se já pago.')) return;
    router.post(route('tenant.admin.requests.process-return', { tenant: tenant.slug, supportRequest: id }));
};

const hasContact = (contact) => contact?.name || contact?.phone || contact?.email;
</script>

<template>
    <Head title="Suporte" />

    <h1 class="admin-page-title">Solicitações dos clientes</h1>
    <p class="mt-3 text-sm text-stone-500">
        Mensagens encaminhadas pelo cardápio. Responda pelo telefone, WhatsApp ou outro canal do seu estabelecimento.
    </p>

    <AdminListSearch
        :href="route('tenant.admin.requests.index', { tenant: tenant.slug })"
        :filters="filters"
        placeholder="Buscar solicitação..."
    />

    <ul v-if="requests?.length" class="mt-6 space-y-3">
        <li v-for="r in requests" :key="r.id" class="admin-card">
            <button type="button" class="w-full text-left" @click="openRequest(r)">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-stone-900">{{ r.subject }}</p>
                        <p class="text-xs text-stone-500">
                            {{ typeLabel(r.type) }}
                            <span v-if="r.order"> · Pedido {{ r.order.order_number }}</span>
                        </p>
                    </div>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                        :class="r.status === 'closed' ? 'bg-stone-100 text-stone-600' : 'bg-amber-100 text-amber-800'"
                    >
                        {{ statusLabel(r.status) }}
                    </span>
                </div>
            </button>

            <div
                v-if="hasContact(r.contact)"
                class="mt-3 rounded-xl border border-stone-200 bg-stone-50 p-3 text-sm"
            >
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Contato do cliente</p>
                <dl class="mt-2 grid gap-2 sm:grid-cols-2">
                    <div v-if="r.contact.name">
                        <dt class="text-xs text-stone-500">Nome</dt>
                        <dd class="font-medium text-stone-900">
                            {{ r.contact.name }}
                            <span
                                v-if="r.contact.has_account"
                                class="ml-1 rounded bg-orange-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-orange-800"
                            >
                                Conta cadastrada
                            </span>
                        </dd>
                    </div>
                    <div v-if="r.contact.phone">
                        <dt class="text-xs text-stone-500">Telefone</dt>
                        <dd class="font-medium text-stone-900">
                            <a
                                v-if="r.contact.whatsapp_url"
                                :href="r.contact.whatsapp_url"
                                target="_blank"
                                rel="noopener"
                                class="text-green-700 hover:underline"
                                @click.stop
                            >
                                {{ r.contact.phone }}
                            </a>
                            <template v-else>{{ r.contact.phone }}</template>
                            <span v-if="r.contact.whatsapp_url" class="ml-1 text-xs text-green-600">(WhatsApp)</span>
                        </dd>
                    </div>
                    <div v-if="r.contact.email" class="sm:col-span-2">
                        <dt class="text-xs text-stone-500">E-mail</dt>
                        <dd>
                            <a
                                :href="`mailto:${r.contact.email}`"
                                class="font-medium text-orange-600 hover:underline"
                                @click.stop
                            >
                                {{ r.contact.email }}
                            </a>
                        </dd>
                    </div>
                </dl>
                <p v-if="r.order" class="mt-2 text-xs text-stone-500">
                    Pedido informado:
                    <Link
                        v-if="tenant"
                        :href="route('tenant.admin.orders.show', { tenant: tenant.slug, order: r.order.id })"
                        class="font-medium text-orange-600 hover:underline"
                        @click.stop
                    >
                        {{ r.order.order_number }}
                    </Link>
                </p>
            </div>
            <p v-else class="mt-3 text-sm text-amber-700">Nenhum contato informado nesta solicitação.</p>

            <p class="mt-3 text-sm text-stone-600">{{ r.message }}</p>
            <p class="mt-1 text-xs text-stone-400">
                {{ r.created_at }}
                <span v-if="r.last_responded_at">
                    · Respondida em {{ r.last_responded_at }}
                    <template v-if="r.last_responded_by_name"> por {{ r.last_responded_by_name }}</template>
                </span>
                <span v-if="r.closed_at">
                    · Encerrada em {{ r.closed_at }}
                    <template v-if="r.closed_by_name"> por {{ r.closed_by_name }}</template>
                </span>
            </p>

            <div v-if="r.activity_logs?.length" class="mt-3 rounded-xl border border-stone-200 bg-stone-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Registro de atividades</p>
                <ul class="mt-2 space-y-2 text-xs text-stone-700">
                    <li v-for="log in r.activity_logs" :key="log.id">
                        <span class="font-medium text-stone-900">{{ log.action_label }}</span>
                        — {{ log.description }}
                        <span class="text-stone-500">
                            ({{ log.created_at }} · {{ log.actor_name }})
                        </span>
                    </li>
                </ul>
            </div>

            <form
                v-if="expandedId === r.id && can('requests.close')"
                class="mt-4 space-y-3 border-t border-stone-100 pt-4"
                @submit.prevent="save(r.id)"
            >
                <div>
                    <label class="mb-1 block text-sm font-medium text-stone-700">Notas internas</label>
                    <textarea v-model="noteForm.admin_notes" class="admin-input" rows="2" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="r.status !== 'in_progress'"
                        type="button"
                        class="admin-btn-secondary"
                        :disabled="noteForm.processing"
                        @click="noteForm.status = 'in_progress'; save(r.id)"
                    >
                        Em andamento
                    </button>
                    <button
                        v-if="r.type === 'return' && r.status !== 'closed' && r.order"
                        type="button"
                        class="rounded-xl bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700"
                        @click="processReturn(r.id)"
                    >
                        Processar devolução
                    </button>
                    <button
                        v-if="r.status !== 'closed'"
                        type="button"
                        class="admin-btn-primary"
                        :disabled="noteForm.processing"
                        @click="noteForm.status = 'closed'; save(r.id)"
                    >
                        Encerrar solicitação
                    </button>
                    <button type="submit" class="admin-btn-secondary" :disabled="noteForm.processing">Salvar notas</button>
                </div>
            </form>

            <button
                v-else-if="can('requests.close')"
                type="button"
                class="mt-3 text-sm font-medium text-orange-600 hover:underline"
                @click="openRequest(r)"
            >
                {{ expandedId === r.id ? 'Ocultar atendimento' : 'Atender / notas internas' }}
            </button>
        </li>
    </ul>

    <p v-else class="mt-12 text-center text-stone-500">Nenhuma solicitação no momento.</p>
</template>
