<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AdminLayout });

defineProps({
    branches: Array,
});

const page = usePage();
const tenant = page.props.tenant;

const setAutoAccept = (branchId, enabled) => {
    router.patch(
        route('tenant.admin.branches.orders-status', { tenant: tenant.slug, branch: branchId }),
        { auto_accept_orders: enabled },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head title="Configuração de pedidos" />

    <h1 class="admin-page-title">Configuração de pedidos</h1>
    <p class="mt-1 max-w-2xl text-sm text-stone-500">
        Defina se os pedidos feitos pelo cardápio digital de cada filial entram automaticamente como confirmados ou
        ficam aguardando aprovação manual no painel e no KDS.
    </p>

    <section class="admin-card mt-8">
        <h2 class="font-semibold text-stone-900">Aprovação automática por filial</h2>
        <p class="mt-1 text-sm text-stone-500">
            Pedidos do PDV e balcão sempre entram confirmados. Esta opção vale apenas para pedidos pelo cardápio online.
        </p>

        <ul v-if="branches?.length" class="mt-6 divide-y divide-stone-100">
            <li
                v-for="branch in branches"
                :key="branch.id"
                class="flex flex-wrap items-center justify-between gap-4 py-4 first:pt-0 last:pb-0"
            >
                <div>
                    <p class="font-medium text-stone-900">{{ branch.name }}</p>
                    <p class="text-sm text-stone-500">/{{ branch.slug }}</p>
                    <span
                        v-if="!branch.is_active"
                        class="mt-1 inline-block rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600"
                    >
                        Filial inativa
                    </span>
                </div>
                <label class="flex cursor-pointer items-center gap-3 text-sm text-stone-700">
                    <span class="text-right">
                        <span class="block font-medium" :class="branch.auto_accept_orders ? 'text-emerald-700' : 'text-amber-800'">
                            {{ branch.auto_accept_orders ? 'Automática' : 'Manual' }}
                        </span>
                        <span class="text-xs text-stone-500">
                            {{ branch.auto_accept_orders ? 'Sem fila de aprovação' : 'Exige confirmação' }}
                        </span>
                    </span>
                    <input
                        type="checkbox"
                        class="h-5 w-5 rounded border-stone-300"
                        :checked="branch.auto_accept_orders"
                        @change="setAutoAccept(branch.id, $event.target.checked)"
                    />
                </label>
            </li>
        </ul>
        <p v-else class="mt-6 text-sm text-stone-500">
            Nenhuma filial cadastrada.
            <a
                :href="route('tenant.admin.branches.create', { tenant: tenant.slug })"
                class="font-medium text-orange-600 hover:underline"
            >
                Criar filial
            </a>
        </p>
    </section>
</template>
