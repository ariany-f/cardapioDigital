<script setup>
import LegalFooter from '@/Components/Public/LegalFooter.vue';
import NavIcon from '@/Components/NavIcon.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const sidebarOpen = ref(false);

const nav = [
    { label: 'Dashboard', route: 'platform.dashboard', icon: 'grid' },
    { label: 'Restaurantes', route: 'platform.tenants.index', icon: 'building' },
    { label: 'Pedidos', route: 'platform.orders.index', icon: 'orders' },
    { label: 'Clientes', route: 'platform.customers.index', icon: 'users' },
    { label: 'Solicitações', route: 'platform.marketing-leads.index', icon: 'message' },
    { label: 'Planos', route: 'platform.plans.index', icon: 'grid' },
    { label: 'Pagamentos', route: 'platform.payments.index', icon: 'payment' },
    { label: 'Avaliações', route: 'platform.ratings.index', icon: 'chart' },
    { label: 'E-mail (SMTP)', route: 'platform.settings.email', icon: 'message' },
    { label: 'SEO', route: 'platform.settings.seo', icon: 'globe' },
    { label: 'Google Maps', route: 'platform.settings.maps', icon: 'map' },
].map((item) => ({
    ...item,
    href: route(item.route),
    active: route().current(item.route) || route().current(`${item.route}.*`),
}));

const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="platform-shell min-h-screen lg:flex">
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden"
            @click="sidebarOpen = false"
        />

        <aside
            class="platform-sidebar fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r transition-transform lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <div class="platform-top-strip shrink-0" aria-hidden="true" />
            <div class="border-b border-slate-800 px-5 py-5">
                <span class="platform-context-badge mb-3">Superadmin</span>
                <div class="flex items-center gap-2">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-500 font-bold text-sm text-white">
                        AC
                    </span>
                    <div>
                        <p class="text-sm font-bold text-slate-100">App Cardápio</p>
                        <p class="text-[10px] font-medium text-slate-400">Plataforma global</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 space-y-0.5 px-3 py-4">
                <Link
                    v-for="item in nav"
                    :key="item.route"
                    :href="item.href"
                    class="platform-sidebar-link"
                    :class="{ 'platform-sidebar-link-active': item.active }"
                    @click="sidebarOpen = false"
                >
                    <span class="platform-sidebar-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-lg">
                        <NavIcon :name="item.icon" />
                    </span>
                    <span class="truncate">{{ item.label }}</span>
                </Link>
            </nav>

            <div class="border-t border-slate-800 p-4">
                <p class="truncate text-sm font-semibold text-slate-100">{{ page.props.auth.user?.name }}</p>
                <p class="truncate text-xs text-slate-400">{{ page.props.auth.user?.email }}</p>
            </div>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col bg-[var(--platform-bg)]">
            <header class="platform-header sticky top-0 z-30 flex items-center justify-between px-4 py-3 backdrop-blur lg:border-b-2 lg:border-indigo-200 lg:px-8">
                <button
                    type="button"
                    class="rounded-xl p-2 text-gray-600 hover:bg-gray-100 lg:hidden"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="hidden items-center gap-2 lg:flex">
                    <span class="platform-context-badge !bg-indigo-100 !text-indigo-700">Superadmin</span>
                    <span class="platform-header-context text-sm font-medium">Plataforma global</span>
                </div>

                <button
                    type="button"
                    class="rounded-full px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
                    @click="logout"
                >
                    Sair
                </button>
            </header>

            <main class="flex-1 px-4 py-6 lg:px-8 lg:py-8">
                <div
                    v-if="page.props.flash?.success"
                    class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                >
                    {{ page.props.flash.success }}
                </div>
                <div
                    v-if="Object.keys(page.props.errors || {}).length"
                    class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                >
                    <p v-for="(msg, key) in page.props.errors" :key="key">{{ msg }}</p>
                </div>
                <slot />
            </main>

            <footer class="shrink-0 border-t border-gray-200 bg-white px-4 py-4 lg:px-8">
                <LegalFooter variant="platform" />
            </footer>
        </div>
    </div>
</template>
