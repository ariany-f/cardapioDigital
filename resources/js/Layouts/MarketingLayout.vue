<script setup>
import { useSeoAnalytics } from '@/composables/useSeoAnalytics';
import { Head, Link, usePage } from '@inertiajs/vue3';

useSeoAnalytics();

const page = usePage();
const year = new Date().getFullYear();

const nav = [
    { label: 'Início', href: '#' },
    { label: 'Como funciona', href: '#como-funciona' },
    { label: 'Recursos', href: '#recursos' },
    { label: 'Planos', href: '#planos' },
    { label: 'Depoimentos', href: '#depoimentos' },
];
</script>

<template>
    <Head>
        <link
            rel="stylesheet"
            href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800&display=swap"
        />
    </Head>

    <div class="marketing-shell min-h-screen font-sans">
        <header class="marketing-header">
            <div class="marketing-wrap flex h-16 items-center justify-between gap-4">
                <Link href="/" class="marketing-logo">
                    <span class="marketing-logo-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none" class="h-7 w-7">
                            <rect width="32" height="32" rx="10" fill="currentColor" />
                            <path
                                d="M9 16.5c0-3.5 2.5-6 6-6s6 2.5 6 6-2.5 6-6 6c-1.2 0-2.3-.3-3.2-.9L9 22v-5.5Z"
                                fill="white"
                            />
                        </svg>
                    </span>
                    <span class="marketing-logo-text">App Cardápio</span>
                </Link>

                <nav class="hidden items-center gap-1 lg:flex">
                    <a
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="marketing-nav-link"
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="#planos" class="marketing-btn-ghost hidden sm:inline-flex">Ver planos</a>
                    <a href="#contato" class="marketing-btn-primary">Quero vender</a>
                </div>
            </div>
        </header>

        <div v-if="page.props.flash?.success" class="marketing-flash">
            <div class="marketing-wrap">{{ page.props.flash.success }}</div>
        </div>

        <main>
            <slot />
        </main>

        <footer class="marketing-footer">
            <div class="marketing-wrap">
                <div class="footer-grid">
                    <div>
                        <p class="footer-brand">App Cardápio</p>
                        <p class="footer-desc">
                            Cardápio digital, pedidos online e gestão completa para restaurantes com delivery próprio.
                        </p>
                    </div>
                    <div>
                        <p class="footer-col-title">Produto</p>
                        <ul class="footer-links">
                            <li><a href="#como-funciona">Como funciona</a></li>
                            <li><a href="#recursos">Recursos</a></li>
                            <li><a href="#planos">Planos</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="footer-col-title">Acesso</p>
                        <ul class="footer-links">
                            <li>
                                <Link :href="route('login')">Entrar no painel</Link>
                            </li>
                            <li><a href="#contato">Falar com a gente</a></li>
                            <li>
                                <Link :href="route('legal.terms')">Termos de uso</Link>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>
                        © {{ year }} App Cardápio. Todos os direitos reservados.
                        <Link :href="route('legal.terms')" class="footer-legal-link">Termos de uso</Link>
                    </p>
                    <p class="footer-made">Feito com ❤️ para quem vende comida</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
.marketing-shell {
    --mk-primary: #f4003a;
    --mk-primary-hover: #d10032;
    --mk-primary-soft: #fff1f4;
    --mk-dark: #1a1a1a;
    --mk-text: #2d2d2d;
    --mk-muted: #6b7280;
    --mk-border: #e5e7eb;
    --mk-bg: #ffffff;
    --mk-bg-soft: #f8f9fa;
    --mk-green: #00a650;
    --mk-green-soft: #e8f8ef;

    background: var(--mk-bg);
    color: var(--mk-text);
    font-family: 'Poppins', system-ui, sans-serif;
}

.marketing-wrap {
    width: 100%;
    max-width: 72rem;
    margin-left: auto;
    margin-right: auto;
    padding-left: 1.25rem;
    padding-right: 1.25rem;
}

.marketing-header {
    position: sticky;
    top: 0;
    z-index: 50;
    border-bottom: 1px solid var(--mk-border);
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px);
}

.marketing-logo {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    text-decoration: none;
}

.marketing-logo-icon {
    color: var(--mk-primary);
}

.marketing-logo-text {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--mk-dark);
    letter-spacing: -0.02em;
}

.marketing-nav-link {
    padding: 0.5rem 0.85rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--mk-muted);
    text-decoration: none;
    border-radius: 9999px;
    transition: color 0.15s, background 0.15s;
}

.marketing-nav-link:hover {
    color: var(--mk-dark);
    background: var(--mk-bg-soft);
}

.marketing-btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: var(--mk-primary);
    padding: 0.6rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    text-decoration: none;
    transition: background 0.15s, transform 0.15s;
    box-shadow: 0 4px 14px -4px rgb(244 0 58 / 0.45);
}

.marketing-btn-primary:hover {
    background: var(--mk-primary-hover);
}

.marketing-btn-ghost {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    padding: 0.6rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--mk-dark);
    text-decoration: none;
    transition: background 0.15s;
}

.marketing-btn-ghost:hover {
    background: var(--mk-bg-soft);
}

.marketing-flash {
    background: var(--mk-green-soft);
    color: var(--mk-green);
    padding: 0.75rem 0;
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
}

.marketing-footer {
    margin-top: 4rem;
    border-top: 1px solid var(--mk-border);
    background: var(--mk-bg-soft);
    padding: 3rem 0 2rem;
}

.footer-grid {
    display: grid;
    gap: 2rem;
}

@media (min-width: 768px) {
    .footer-grid {
        grid-template-columns: 1.4fr 1fr 1fr;
    }
}

.footer-brand {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--mk-dark);
}

.footer-desc {
    margin-top: 0.5rem;
    max-width: 20rem;
    font-size: 0.875rem;
    line-height: 1.6;
    color: var(--mk-muted);
}

.footer-col-title {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--mk-muted);
}

.footer-links {
    margin: 0.75rem 0 0;
    padding: 0;
    list-style: none;
}

.footer-links a {
    display: block;
    padding: 0.25rem 0;
    font-size: 0.875rem;
    color: var(--mk-text);
    text-decoration: none;
}

.footer-links a:hover {
    color: var(--mk-primary);
}

.footer-bottom {
    margin-top: 2.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--mk-border);
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: var(--mk-muted);
}

.footer-made {
    color: var(--mk-primary);
    font-weight: 500;
}

.footer-legal-link {
    margin-left: 0.35rem;
    font-weight: 500;
    color: var(--mk-text);
    text-decoration: underline;
    text-underline-offset: 2px;
}

.footer-legal-link:hover {
    color: var(--mk-primary);
}
</style>
