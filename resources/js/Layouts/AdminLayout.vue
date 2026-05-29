<script setup>
import LegalFooter from '@/Components/Public/LegalFooter.vue';
import NavIcon from '@/Components/NavIcon.vue';
import { useAdminChatUnread } from '@/composables/useAdminChatUnread';
import { useAdminOrdersPending } from '@/composables/useAdminOrdersPending';
import { usePermissions } from '@/composables/usePermissions';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const { can } = usePermissions();
const { totalUnread: chatUnreadTotal } = useAdminChatUnread();
const { pendingTotal: ordersPendingTotal } = useAdminOrdersPending();
const sidebarOpen = ref(false);

const tenant = computed(() => page.props.tenant);
const user = computed(() => page.props.auth?.user);
const isPlatformUser = computed(() => !!user.value?.is_platform_user);
const platformTenantUrl = computed(() =>
    tenant.value?.id ? route('platform.tenants.show', tenant.value.id) : null,
);

const navItems = computed(() => {
    if (!tenant.value) return [];
    const slug = tenant.value.slug;
    const items = [
        { label: 'Painel', route: 'tenant.admin.dashboard', icon: 'grid' },
        { label: 'Chat', route: 'tenant.admin.chat.index', permission: 'chat.access', icon: 'message' },
        { label: 'Pedidos', route: 'tenant.admin.orders.index', permission: 'orders.view', icon: 'receipt' },
        {
            label: 'Aprovação',
            route: 'tenant.admin.orders.settings',
            permission: 'orders.accept',
            icon: 'check',
        },
        { label: 'Registro', route: 'tenant.admin.activity-logs.index', permission: 'orders.view', icon: 'clock' },
        {
            label: 'KDS',
            route: 'tenant.admin.kds',
            permission: 'kds.access',
            icon: 'flame',
            requiresKds: true,
        },
        {
            label: 'PDV',
            route: 'tenant.admin.pos',
            permission: 'orders.pos',
            icon: 'cart',
            requiresPos: true,
        },
        { label: 'Filiais', route: 'tenant.admin.branches.index', permission: 'branches.manage', icon: 'store' },
        { label: 'Mesas', route: 'tenant.admin.tables.index', permission: 'branches.manage', icon: 'table' },
        { label: 'Categorias', route: 'tenant.admin.categories.index', permission: 'products.manage', icon: 'folder' },
        { label: 'Produtos', route: 'tenant.admin.products.index', permission: 'products.manage', icon: 'box' },
        { label: 'Combos', route: 'tenant.admin.combos.index', permission: 'products.manage', icon: 'combo' },
        { label: 'Banners', route: 'tenant.admin.banners.index', permission: 'products.manage', icon: 'banner' },
        {
            label: 'Entregadores',
            route: 'tenant.admin.motoboys.index',
            permission: 'deliveries.update',
            icon: 'moto',
            requiresMotoboys: true,
        },
        {
            label: 'Denúncias',
            route: 'tenant.admin.motoboy-reports.index',
            permission: 'deliveries.update',
            icon: 'help',
            requiresMotoboys: true,
        },
        { label: 'Webhooks', route: 'tenant.admin.webhooks.index', permission: 'deliveries.update', icon: 'settings' },
        { label: 'Cupons', route: 'tenant.admin.coupons.index', permission: 'coupons.manage', icon: 'ticket' },
        { label: 'Idiomas', route: 'tenant.admin.languages.index', permission: 'products.manage', icon: 'globe' },
        { label: 'Suporte', route: 'tenant.admin.requests.index', permission: 'requests.view', icon: 'help' },
        { label: 'Avaliações', route: 'tenant.admin.ratings.index', permission: 'ratings.manage', icon: 'chart' },
        { label: 'Relatórios', route: 'tenant.admin.reports.index', permission: 'reports.view', icon: 'chart' },
        { label: 'Configurações', route: 'tenant.admin.settings', permission: 'users.manage', icon: 'settings' },
        { label: 'Plano', route: 'tenant.admin.plan.index', permission: 'users.manage', icon: 'grid' },
        { label: 'Usuários', route: 'tenant.admin.users.index', permission: 'users.manage', icon: 'users' },
    ];

    const motoboysEnabled = tenant.value?.motoboys_enabled !== false;
    const posEnabled = tenant.value?.pos_enabled !== false;
    const kdsEnabled = tenant.value?.kds_enabled !== false;

    return items
        .filter((item) => !item.permission || can(item.permission))
        .filter((item) => !item.requiresMotoboys || motoboysEnabled)
        .filter((item) => !item.requiresPos || posEnabled)
        .filter((item) => !item.requiresKds || kdsEnabled)
        .map((item) => ({
            ...item,
            href: route(item.route, { tenant: slug }),
            active: route().current(item.route),
        }));
});

const logout = () => router.post(route('logout'));
</script>

<template>
    <div
        class="admin-shell min-h-screen lg:flex lg:h-screen lg:overflow-hidden"
        :style="{ '--admin-accent': tenant?.theme_primary_color || '#f4003a' }"
    >
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden"
            @click="sidebarOpen = false"
        />

        <aside
            class="admin-sidebar fixed inset-y-0 left-0 z-50 flex h-screen max-h-screen w-64 flex-col overflow-hidden border-r border-gray-200 transition-transform lg:static lg:shrink-0 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <div class="admin-top-strip shrink-0" aria-hidden="true" />
            <div class="border-b border-gray-200 bg-white px-5 py-5">
                <span class="admin-context-badge mb-3">Restaurante</span>
                <div class="flex items-center gap-2">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-white"
                        :style="{ backgroundColor: 'var(--admin-accent)' }"
                    >
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-gray-900">{{ tenant?.name }}</p>
                        <p class="text-[10px] font-medium text-gray-500">Painel administrativo</p>
                    </div>
                </div>
                <Link
                    v-if="isPlatformUser && platformTenantUrl"
                    :href="platformTenantUrl"
                    class="mt-3 block text-xs font-medium text-brand hover:underline"
                >
                    ← Voltar à plataforma
                </Link>
            </div>

            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
                <Link
                    v-for="item in navItems"
                    :key="item.route"
                    :href="item.href"
                    class="admin-sidebar-link"
                    :class="{ 'admin-sidebar-link-active': item.active }"
                    @click="sidebarOpen = false"
                >
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500"
                        :class="{ '!bg-white/20 !text-white': item.active }"
                    >
                        <NavIcon :name="item.icon" />
                    </span>
                    <span class="truncate">{{ item.label }}</span>
                    <span
                        v-if="item.route === 'tenant.admin.orders.index' && ordersPendingTotal > 0"
                        class="ml-auto flex min-h-[1.125rem] min-w-[1.125rem] shrink-0 items-center justify-center rounded-full bg-amber-400 px-1 text-[10px] font-bold leading-none text-amber-950"
                        :class="{ '!bg-white !text-amber-950': item.active }"
                    >
                        {{ ordersPendingTotal > 99 ? '99+' : ordersPendingTotal }}
                    </span>
                    <span
                        v-if="item.route === 'tenant.admin.chat.index' && chatUnreadTotal > 0"
                        class="ml-auto flex min-h-[1.125rem] min-w-[1.125rem] shrink-0 items-center justify-center rounded-full bg-white px-1 text-[10px] font-bold leading-none text-brand"
                    >
                        {{ chatUnreadTotal > 99 ? '99+' : chatUnreadTotal }}
                    </span>
                </Link>
            </nav>

            <div class="border-t border-gray-100 p-4">
                <p class="truncate text-sm font-semibold text-gray-900">{{ user?.name }}</p>
                <p class="truncate text-xs text-gray-500">{{ user?.email }}</p>
            </div>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col bg-[var(--admin-bg)]">
            <header class="sticky top-0 z-30 flex items-center justify-between border-b border-gray-200 bg-white/95 px-4 py-3 backdrop-blur lg:border-b-2 lg:px-8" :style="{ borderBottomColor: 'color-mix(in srgb, var(--admin-accent) 25%, #e5e7eb)' }">
                <button
                    type="button"
                    class="rounded-xl p-2 text-gray-600 hover:bg-gray-100 lg:hidden"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <span class="sr-only">Menu</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="hidden items-center gap-2 lg:flex">
                    <span class="admin-context-badge">Restaurante</span>
                    <span class="text-sm font-medium text-gray-600">
                        {{ tenant?.name }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="isPlatformUser && platformTenantUrl"
                        :href="platformTenantUrl"
                        class="rounded-full px-3 py-2 text-sm font-medium text-brand transition hover:bg-brand-soft"
                    >
                        Plataforma
                    </Link>
                    <Link
                        v-if="tenant"
                        :href="route('tenant.home', { tenant: tenant.slug })"
                        class="rounded-full px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
                    >
                        Ver site
                    </Link>
                    <button
                        type="button"
                        class="rounded-full px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
                        @click="logout"
                    >
                        Sair
                    </button>
                </div>
            </header>

            <main class="min-h-0 flex-1 overflow-y-auto px-4 py-6 lg:px-8 lg:py-8">
                <div
                    v-if="isPlatformUser"
                    class="mb-6 rounded-2xl border border-brand/20 bg-brand-soft px-4 py-3 text-sm text-gray-800"
                >
                    Você está no painel completo deste restaurante.
                    <Link v-if="platformTenantUrl" :href="platformTenantUrl" class="ml-1 font-semibold text-brand hover:underline">
                        Voltar à plataforma
                    </Link>
                </div>
                <div
                    v-if="page.props.flash?.success"
                    class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                >
                    {{ page.props.flash.success }}
                </div>
                <div
                    v-if="page.props.flash?.error"
                    class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                >
                    {{ page.props.flash.error }}
                </div>
                <slot />
            </main>

            <footer class="shrink-0 border-t border-gray-200 bg-white px-4 py-4 lg:px-8">
                <LegalFooter :tenant-slug="tenant?.slug" variant="admin" />
            </footer>
        </div>
    </div>
</template>
