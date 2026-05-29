<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen">
        <aside
            class="hidden w-[42%] max-w-xl flex-col justify-between bg-gradient-to-br from-[#f4003a] to-[#d10032] p-10 text-white lg:flex"
        >
            <div>
                <div class="flex items-center gap-2">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20 text-lg font-bold">
                        AC
                    </span>
                    <span class="text-lg font-bold">App Cardápio</span>
                </div>
                <h1 class="mt-8 text-3xl font-bold leading-tight">Painel administrativo</h1>
                <p class="mt-4 max-w-sm text-white/85">
                    Pedidos, cardápio, cozinha e entregas — tudo num lugar só, no estilo dos melhores apps de delivery.
                </p>
            </div>
            <ul class="space-y-3 text-sm text-white/80">
                <li class="flex items-center gap-2">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-xs">✓</span>
                    Pedidos, PDV e cozinha (KDS)
                </li>
                <li class="flex items-center gap-2">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-xs">✓</span>
                    Cardápio, filiais e entregas
                </li>
                <li class="flex items-center gap-2">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-xs">✓</span>
                    Superadmin: gestão de todos os restaurantes
                </li>
            </ul>
        </aside>

        <main class="flex flex-1 flex-col justify-center bg-gray-50 px-6 py-12 sm:px-12">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <p class="text-xs font-bold uppercase tracking-wider text-brand">App Cardápio</p>
                    <h1 class="mt-1 text-xl font-bold text-gray-900">Painel administrativo</h1>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
                    <slot />
                </div>

                <p class="mt-6 text-center text-xs text-gray-400">
                    Clientes finais:
                    <Link href="/conta/login" class="font-semibold text-brand hover:underline">área da conta</Link>
                </p>
            </div>
        </main>
    </div>
</template>
