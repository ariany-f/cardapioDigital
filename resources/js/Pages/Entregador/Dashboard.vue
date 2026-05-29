<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    pendingAssignments: Array,
    activeDeliveries: Array,
});

const page = usePage();
const tenant = page.props.tenant?.slug;
const codeForms = ref({});

const respond = (deliveryId, accept) => {
    const reason = accept ? null : prompt('Motivo da recusa (opcional)') || '';
    router.post(route('tenant.entregador.deliveries.respond', { tenant, delivery: deliveryId }), {
        accept,
        reject_reason: reason,
    });
};

const submitStatus = (deliveryId) => {
    const data = codeForms.value[deliveryId] || {};
    router.patch(route('tenant.entregador.deliveries.status', { tenant, delivery: deliveryId }), data);
};

let pollTimer = null;
onMounted(() => {
    pollTimer = setInterval(async () => {
        const { data } = await axios.get(route('tenant.entregador.poll', { tenant }));
        if (data.pending_count > 0) {
            router.reload({ only: ['pendingAssignments', 'activeDeliveries'] });
        }
    }, 5000);
});
onUnmounted(() => pollTimer && clearInterval(pollTimer));

const logout = () => router.post(route('tenant.entregador.logout', { tenant }));
</script>

<template>
    <Head title="Minhas entregas" />
    <div class="min-h-screen bg-stone-100">
        <header class="bg-brand px-4 py-4 text-white">
            <div class="mx-auto flex max-w-lg items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold">Minhas entregas</h1>
                    <p class="text-xs text-white/80">Painel web · atualize o status de cada pedido</p>
                </div>
                <button type="button" class="text-sm underline" @click="logout">Sair</button>
            </div>
        </header>

        <main class="mx-auto max-w-lg space-y-6 p-4">
            <section v-if="pendingAssignments?.length">
                <h2 class="mb-2 font-semibold text-amber-800">Novos pedidos — aceitar?</h2>
                <article v-for="d in pendingAssignments" :key="d.id" class="rounded-xl border-2 border-amber-200 bg-white p-4">
                    <p class="font-bold">#{{ d.order.order_number }}</p>
                    <p class="text-sm text-stone-600">{{ d.order.guest_name }} · {{ d.branch?.name }}</p>
                    <p class="text-sm">{{ d.order.delivery_address }}</p>
                    <div class="mt-3 flex gap-2">
                        <button type="button" class="flex-1 rounded-lg bg-emerald-600 py-2 text-sm font-semibold text-white" @click="respond(d.id, true)">
                            Aceitar
                        </button>
                        <button type="button" class="flex-1 rounded-lg border border-red-200 py-2 text-sm text-red-700" @click="respond(d.id, false)">
                            Recusar
                        </button>
                    </div>
                </article>
            </section>

            <section>
                <h2 class="mb-2 font-semibold">Em andamento</h2>
                <p v-if="!activeDeliveries?.length" class="text-sm text-stone-500">Nenhuma entrega ativa.</p>
                <article v-for="d in activeDeliveries" :key="'a-' + d.id" class="mb-3 rounded-xl bg-white p-4 shadow-sm">
                    <p class="font-bold">#{{ d.order.order_number }}</p>
                    <p class="text-sm">{{ d.order.guest_name }} — {{ d.order.guest_phone }}</p>
                    <p class="text-sm text-stone-600">{{ d.order.delivery_address }}</p>
                    <p class="mt-1 text-sm font-medium">Status: {{ d.status }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" class="rounded-lg bg-stone-800 px-3 py-1.5 text-xs text-white" @click="codeForms[d.id] = { delivery_status: 'picked_up' }; submitStatus(d.id)">
                            Coletado
                        </button>
                        <button type="button" class="rounded-lg bg-stone-800 px-3 py-1.5 text-xs text-white" @click="codeForms[d.id] = { delivery_status: 'on_route' }; submitStatus(d.id)">
                            A caminho
                        </button>
                    </div>
                    <div class="mt-3 border-t pt-3">
                        <input
                            v-model="codeForms[d.id].confirmation_code"
                            class="w-full rounded-lg border px-3 py-2 text-sm"
                            placeholder="Código do cliente (6 dígitos)"
                            maxlength="8"
                        />
                        <button
                            type="button"
                            class="mt-2 w-full rounded-lg bg-emerald-600 py-2 text-sm font-semibold text-white"
                            @click="codeForms[d.id] = { ...codeForms[d.id], delivery_status: 'delivered' }; submitStatus(d.id)"
                        >
                            Confirmar entrega
                        </button>
                    </div>
                </article>
            </section>
        </main>
    </div>
</template>
