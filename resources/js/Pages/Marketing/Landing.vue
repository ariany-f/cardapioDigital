<script setup>
import SeoHead from '@/Components/SeoHead.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useForm } from '@inertiajs/vue3';

defineOptions({ layout: MarketingLayout });

const props = defineProps({
    seo: Object,
    plans: { type: Array, default: () => [] },
    featuredPlan: Object,
    features: Array,
    workflow: Array,
    heroMock: Object,
    categories: Array,
    benefits: Array,
    audiences: Array,
    stats: Array,
    testimonials: Array,
});

const featureLabels = {
    max_branches: 'Filiais',
    kds: 'KDS (cozinha)',
    pos: 'PDV (balcão)',
    reports: 'Relatórios',
    delivery_webhooks: 'Integrações de entrega',
    motoboys: 'Módulo de entregadores',
};

const planHighlights = (plan) => {
    const f = plan.features_json ?? {};
    const rows = [];

    if (f.max_branches != null) {
        rows.push(`Até ${f.max_branches} filial${f.max_branches === 1 ? '' : 'is'}`);
    }

    for (const [key, label] of Object.entries(featureLabels)) {
        if (key === 'max_branches') {
            continue;
        }
        if (f[key]) {
            rows.push(label);
        }
    }

    rows.push('Cardápio e combos por filial');
    rows.push('Pedidos online');
    rows.push('PIX no checkout');
    rows.push('Chat com cliente');
    rows.push('Suporte incluso');

    return [...new Set(rows)];
};

const form = useForm({
    restaurant_name: '',
    contact_name: '',
    email: '',
    phone: '',
    city: '',
    message: '',
});

const submit = () =>
    form.post(route('marketing.contact'), { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <SeoHead :seo="seo" />

    <!-- Hero — primeira dobra -->
    <section class="hero-section">
        <div class="marketing-wrap hero-grid">
            <div class="hero-copy">
                <p class="hero-eyebrow">Lanchonete · pizzaria · hamburgueria</p>
                <h1 class="hero-h1">
                    Pare de perder pedido<br />
                    no <span class="hero-strike">WhatsApp</span> bagunçado.
                </h1>
                <p class="hero-lead">
                    Um link com seu cardápio. O cliente monta o pedido no celular. A cozinha vê no KDS,
                    o entregador atualiza pelo painel web e você confirma o PIX quando o dinheiro cair —
                    <strong>sem comissão em cada venda</strong>, só a mensalidade fixa.
                </p>
                <div class="hero-cta">
                    <a href="#contato" class="btn-red">Quero na minha loja</a>
                    <a href="#planos" class="btn-ghost">
                        {{ featuredPlan?.name ?? 'Planos' }} · R$ {{ featuredPlan?.price_formatted }}/mês →
                    </a>
                </div>
                <ul class="hero-footnotes">
                    <li>Teste de 7 dias</li>
                    <li>Sem contrato de fidelidade</li>
                    <li>Suporte direto com quem fez o sistema</li>
                </ul>
            </div>

            <aside v-if="heroMock" class="hero-mock" aria-label="Exemplo de pedido no painel">
                <div class="mock-phone">
                    <div class="mock-notch" />
                    <div class="mock-screen">
                        <div class="mock-appbar">
                            <span class="mock-dot mock-dot-live" />
                            Pedidos ao vivo
                        </div>
                        <div class="mock-ticket">
                            <div class="mock-ticket-top">
                                <span class="mock-badge">{{ heroMock.order_number }}</span>
                                <span class="mock-time">{{ heroMock.time }}</span>
                            </div>
                            <p class="mock-branch">{{ heroMock.branch }}</p>
                            <ul class="mock-lines">
                                <li v-for="line in heroMock.items" :key="line.name">
                                    <span>{{ line.qty }}× {{ line.name }}</span>
                                    <span>R$ {{ line.price }}</span>
                                </li>
                            </ul>
                            <div class="mock-total">
                                <span>Total</span>
                                <strong>R$ {{ heroMock.total }}</strong>
                            </div>
                            <div class="mock-status">
                                <span class="mock-status-dot" />
                                <div>
                                    <p class="mock-status-title">{{ heroMock.status }}</p>
                                    <p class="mock-status-sub">{{ heroMock.status_detail }}</p>
                                </div>
                            </div>
                            <p class="mock-pay">{{ heroMock.payment }}</p>
                        </div>
                    </div>
                </div>
                <p class="mock-caption">É assim que seu time enxerga cada pedido — sem print apagado no grupo.</p>
            </aside>
        </div>
    </section>

    <!-- Como funciona -->
    <section id="como-funciona" class="section section-white">
        <div class="marketing-wrap">
            <h2 class="section-h2">Pedir no seu cardápio é simples demais</h2>
            <div class="steps-grid">
                <article v-for="(step, i) in workflow" :key="step.title" class="step-card">
                    <span class="step-num">{{ i + 1 }}</span>
                    <h3>{{ step.title }}</h3>
                    <p>{{ step.text }}</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Categorias -->
    <section id="categorias" class="section section-soft">
        <div class="marketing-wrap">
            <h2 class="section-h2">Para todo tipo de negócio</h2>
            <p class="section-lead">Lanchonete, pizzaria, mercado, farmácia… o App Cardápio se adapta.</p>
            <div class="categories-scroll scrollbar-hide">
                <div v-for="cat in categories" :key="cat.name" class="category-card">
                    <span class="category-emoji">{{ cat.emoji }}</span>
                    <p>{{ cat.name }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefícios -->
    <section id="recursos" class="section section-white">
        <div class="marketing-wrap">
            <h2 class="section-h2">Muito além do cardápio online</h2>
            <div class="benefits-grid">
                <article v-for="(b, i) in benefits" :key="b.title" class="benefit-card" :class="{ 'benefit-card-alt': i % 2 === 1 }">
                    <span class="benefit-icon" aria-hidden="true">{{ ['🍽️', '👨‍🍳', '🛵'][i] || '✨' }}</span>
                    <h3>{{ b.title }}</h3>
                    <p>{{ b.text }}</p>
                </article>
            </div>

            <div class="features-grid">
                <article v-for="f in features" :key="f.title" class="feature-mini">
                    <span class="feature-dot" />
                    <div>
                        <h4>{{ f.title }}</h4>
                        <p>{{ f.text }}</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- Públicos -->
    <section class="section section-soft">
        <div class="marketing-wrap">
            <h2 class="section-h2">Quem usa, cresce. Quem vende, fica com a margem.</h2>
            <div class="audience-grid">
                <article v-for="a in audiences" :key="a.title" class="audience-card">
                    <h3>{{ a.title }}</h3>
                    <p>{{ a.text }}</p>
                    <a :href="a.href" class="audience-link">{{ a.cta }} →</a>
                </article>
            </div>
        </div>
    </section>

    <!-- Números -->
    <section class="section section-white">
        <div class="marketing-wrap">
            <h2 class="section-h2">Tudo que você precisa, num só lugar</h2>
            <div class="stats-grid">
                <div v-for="s in stats" :key="s.value" class="stat-card">
                    <p class="stat-value">{{ s.value }}</p>
                    <p class="stat-label">{{ s.label }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Depoimentos -->
    <section id="depoimentos" class="section section-soft">
        <div class="marketing-wrap">
            <h2 class="section-h2">Quem já usa, recomenda</h2>
            <div class="testimonials-grid">
                <blockquote v-for="t in testimonials" :key="t.name" class="testimonial-card">
                    <p class="testimonial-text">"{{ t.text }}"</p>
                    <footer>
                        <strong>{{ t.name }}</strong>
                        <span>{{ t.role }}</span>
                    </footer>
                </blockquote>
            </div>
        </div>
    </section>

    <!-- Planos -->
    <section id="planos" class="section section-white">
        <div class="marketing-wrap">
            <h2 class="section-h2">Um preço fixo. Sem comissão escondida.</h2>
            <p class="section-lead">
                Enquanto marketplaces cobram até 27% por pedido, aqui você paga só a mensalidade e fica com a margem da sua comida.
            </p>

            <ul class="pricing-compare pricing-compare-centered">
                <li><span>Apps de delivery</span><strong class="text-muted">3% a 27% por pedido</strong></li>
                <li>
                    <span>App Cardápio</span>
                    <strong class="text-brand">a partir de R$ {{ featuredPlan?.price_formatted }}/mês</strong>
                </li>
            </ul>

            <div class="pricing-plans-grid">
                <article
                    v-for="plan in plans"
                    :key="plan.slug"
                    class="price-box"
                    :class="{ 'price-box-featured': plan.is_featured }"
                >
                    <p v-if="plan.is_featured" class="price-badge">Menor preço</p>
                    <p class="price-tag">Plano {{ plan.name }}</p>
                    <p class="price-main">
                        <span class="price-currency">R$</span>{{ plan.price_formatted }}
                        <span class="price-period">/mês</span>
                    </p>
                    <ul class="price-list">
                        <li v-for="item in planHighlights(plan)" :key="item">{{ item }}</li>
                    </ul>
                    <a href="#contato" class="btn-red btn-block">Solicitar ativação</a>
                    <p class="price-note">Cancele quando quiser · sem fidelidade</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Contato -->
    <section id="contato" class="section section-cta">
        <div class="marketing-wrap contact-row">
            <div>
                <h2 class="section-h2 text-white">Quer levar o App Cardápio para sua loja?</h2>
                <p class="cta-lead">Manda seus dados — respondemos com o passo a passo para ativar seu cardápio.</p>
            </div>
            <form class="contact-box" @submit.prevent="submit">
                <div class="form-field">
                    <label for="restaurant_name">Nome do restaurante</label>
                    <input id="restaurant_name" v-model="form.restaurant_name" type="text" required placeholder="Ex.: Pizzaria do Zé" />
                    <p v-if="form.errors.restaurant_name" class="form-error">{{ form.errors.restaurant_name }}</p>
                </div>
                <div class="form-field">
                    <label for="contact_name">Seu nome</label>
                    <input id="contact_name" v-model="form.contact_name" type="text" required />
                    <p v-if="form.errors.contact_name" class="form-error">{{ form.errors.contact_name }}</p>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="email">E-mail</label>
                        <input id="email" v-model="form.email" type="email" required />
                        <p v-if="form.errors.email" class="form-error">{{ form.errors.email }}</p>
                    </div>
                    <div class="form-field">
                        <label for="phone">WhatsApp</label>
                        <input id="phone" v-model="form.phone" type="tel" placeholder="(11) 98765-4321" />
                    </div>
                </div>
                <div class="form-field">
                    <label for="city">Cidade</label>
                    <input id="city" v-model="form.city" type="text" placeholder="São Paulo — SP" />
                </div>
                <div class="form-field">
                    <label for="message">Mensagem <span class="optional">(opcional)</span></label>
                    <textarea id="message" v-model="form.message" rows="3" placeholder="Quantas unidades? Já usa outro sistema?" />
                </div>
                <button type="submit" class="btn-red btn-block" :disabled="form.processing">
                    {{ form.processing ? 'Enviando…' : 'Enviar solicitação' }}
                </button>
            </form>
        </div>
    </section>
</template>

<style scoped>
.hero-section {
    position: relative;
    overflow: hidden;
    padding: 2.5rem 0 3.5rem;
    background-color: #faf9f7;
    background-image: radial-gradient(circle at 1px 1px, #e8e4df 1px, transparent 0);
    background-size: 22px 22px;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: -20%;
    right: -10%;
    width: min(50vw, 420px);
    height: min(50vw, 420px);
    border-radius: 50%;
    background: radial-gradient(circle, rgb(244 0 58 / 0.07) 0%, transparent 70%);
    pointer-events: none;
}

.hero-grid {
    position: relative;
    display: grid;
    gap: 2.5rem;
    align-items: center;
}

@media (min-width: 1024px) {
    .hero-grid {
        grid-template-columns: 1.05fr 0.95fr;
        gap: 3rem;
    }
}

.hero-eyebrow {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #f4003a;
}

.hero-h1 {
    margin-top: 0.75rem;
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    line-height: 1.08;
    letter-spacing: -0.03em;
    color: #1a1a1a;
}

.hero-strike {
    position: relative;
    color: #9ca3af;
    font-weight: 700;
}

.hero-strike::after {
    content: '';
    position: absolute;
    left: -2%;
    right: -2%;
    top: 52%;
    height: 3px;
    background: #f4003a;
    transform: rotate(-2deg);
    border-radius: 2px;
}

.hero-lead {
    margin-top: 1.25rem;
    max-width: 34rem;
    font-size: 1.05rem;
    line-height: 1.7;
    color: #4b5563;
}

.hero-lead strong {
    color: #1a1a1a;
    font-weight: 600;
}

.hero-cta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem 1.25rem;
    margin-top: 1.75rem;
}

.hero-footnotes {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem 1.25rem;
    margin: 1.5rem 0 0;
    padding: 0;
    list-style: none;
    font-size: 0.8125rem;
    color: #6b7280;
}

.hero-footnotes li:not(:first-child)::before {
    content: '· ';
    color: #d1d5db;
}

.btn-ghost {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #374151;
    text-decoration: none;
}

.btn-ghost:hover {
    color: #f4003a;
}

/* Mock do pedido */
.hero-mock {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.mock-phone {
    width: min(100%, 300px);
    padding: 0.65rem;
    background: #1a1a1a;
    border-radius: 2rem;
    box-shadow:
        0 24px 48px -12px rgb(0 0 0 / 0.35),
        0 0 0 1px rgb(255 255 255 / 0.08) inset;
    transform: rotate(2deg);
}

.mock-notch {
    width: 5rem;
    height: 0.35rem;
    margin: 0 auto 0.5rem;
    border-radius: 999px;
    background: #333;
}

.mock-screen {
    border-radius: 1.35rem;
    background: #f3f4f6;
    padding: 0.65rem;
    overflow: hidden;
}

.mock-appbar {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.5rem 0.65rem;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #6b7280;
}

.mock-dot {
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 50%;
    background: #9ca3af;
}

.mock-dot-live {
    background: #22c55e;
    box-shadow: 0 0 0 3px rgb(34 197 94 / 0.35);
    animation: mock-pulse 2s ease-in-out infinite;
}

@keyframes mock-pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.mock-ticket {
    background: #fffef9;
    border: 1.5px solid #1a1a1a;
    border-radius: 0.35rem;
    padding: 1rem 1.1rem;
    font-family: ui-monospace, 'Cascadia Code', monospace;
    font-size: 0.75rem;
}

.mock-ticket-top {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 0.5rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.mock-badge {
    padding: 0.15rem 0.4rem;
    border: 1px solid #1a1a1a;
    background: #faf9f7;
}

.mock-time {
    font-weight: 500;
    text-transform: none;
    letter-spacing: 0;
    color: #6b7280;
    font-size: 0.6875rem;
}

.mock-branch {
    margin-top: 0.6rem;
    font-family: inherit;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #1a1a1a;
    letter-spacing: -0.01em;
}

.mock-lines {
    margin: 0.85rem 0 0;
    padding: 0.75rem 0 0;
    border-top: 1px dashed #d1d5db;
    list-style: none;
}

.mock-lines li {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.3rem 0;
    color: #374151;
}

.mock-total {
    display: flex;
    justify-content: space-between;
    margin-top: 0.65rem;
    padding-top: 0.65rem;
    border-top: 2px solid #1a1a1a;
    font-weight: 700;
    color: #1a1a1a;
}

.mock-total strong {
    font-size: 1.05rem;
}

.mock-status {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-top: 0.85rem;
    padding: 0.55rem 0.6rem;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
}

.mock-status-dot {
    flex-shrink: 0;
    width: 0.5rem;
    height: 0.5rem;
    margin-top: 0.2rem;
    border-radius: 50%;
    background: #10b981;
}

.mock-status-title {
    font-family: inherit;
    font-size: 0.75rem;
    font-weight: 700;
    color: #065f46;
}

.mock-status-sub {
    font-family: inherit;
    font-size: 0.6875rem;
    color: #047857;
    margin-top: 0.1rem;
}

.mock-pay {
    margin-top: 0.65rem;
    font-family: inherit;
    font-size: 0.6875rem;
    color: #6b7280;
}

.mock-caption {
    margin-top: 1rem;
    max-width: 16rem;
    text-align: center;
    font-size: 0.8125rem;
    line-height: 1.5;
    color: #6b7280;
}

.section {
    padding: 4rem 0;
}

.section-white {
    background: #fff;
}

.section-soft {
    background: #f8f9fa;
}

.section-cta {
    background: linear-gradient(135deg, #f4003a, #d10032);
    padding-bottom: 5rem;
}

.section-h2 {
    font-size: clamp(1.5rem, 3.5vw, 2.125rem);
    font-weight: 700;
    letter-spacing: -0.02em;
    color: #1a1a1a;
    text-align: center;
}

.section-cta .section-h2 {
    color: #fff;
    text-align: left;
}

.section-lead {
    margin: 0.75rem auto 2rem;
    max-width: 36rem;
    text-align: center;
    font-size: 1rem;
    line-height: 1.6;
    color: #6b7280;
}

.cta-lead {
    margin-top: 0.75rem;
    max-width: 28rem;
    font-size: 1rem;
    line-height: 1.6;
    color: rgb(255 255 255 / 0.9);
}

.steps-grid {
    display: grid;
    gap: 1rem;
    margin-top: 2.5rem;
}

@media (min-width: 768px) {
    .steps-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.step-card {
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 1.25rem;
    text-align: center;
}

.step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: #f4003a;
    color: #fff;
    font-size: 1.125rem;
    font-weight: 700;
}

.step-card h3 {
    margin-top: 1rem;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #1a1a1a;
}

.step-card p {
    margin-top: 0.4rem;
    font-size: 0.8125rem;
    line-height: 1.5;
    color: #6b7280;
}

.categories-scroll {
    display: flex;
    gap: 0.75rem;
    overflow-x: auto;
    padding-bottom: 0.5rem;
}

.category-card {
    flex-shrink: 0;
    width: 7.5rem;
    padding: 1.25rem 0.75rem;
    text-align: center;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgb(0 0 0 / 0.04);
}

.category-emoji {
    font-size: 2rem;
    display: block;
}

.category-card p {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #374151;
}

.benefits-grid {
    display: grid;
    gap: 1.25rem;
    margin-top: 2.5rem;
}

@media (min-width: 900px) {
    .benefits-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.benefit-card {
    padding: 1.75rem;
    background: #fff1f4;
    border-radius: 1.5rem;
    border: 1px solid rgb(244 0 58 / 0.1);
}

.benefit-card-alt {
    background: #fff;
    border-color: #e5e7eb;
}

.benefit-icon {
    font-size: 2rem;
}

.benefit-card h3 {
    margin-top: 0.75rem;
    font-size: 1.0625rem;
    font-weight: 700;
    color: #1a1a1a;
}

.benefit-card p {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.55;
    color: #6b7280;
}

.features-grid {
    display: grid;
    gap: 1rem;
    margin-top: 2.5rem;
}

@media (min-width: 640px) {
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .features-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.feature-mini {
    display: flex;
    gap: 0.75rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 1rem;
}

.feature-dot {
    flex-shrink: 0;
    width: 0.5rem;
    height: 0.5rem;
    margin-top: 0.4rem;
    border-radius: 50%;
    background: #f4003a;
}

.feature-mini h4 {
    font-size: 0.875rem;
    font-weight: 700;
    color: #1a1a1a;
}

.feature-mini p {
    margin-top: 0.25rem;
    font-size: 0.8125rem;
    line-height: 1.45;
    color: #6b7280;
}

.audience-grid {
    display: grid;
    gap: 1.25rem;
    margin-top: 2.5rem;
}

@media (min-width: 900px) {
    .audience-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.audience-card {
    padding: 1.75rem;
    background: #fff;
    border-radius: 1.5rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 20px rgb(0 0 0 / 0.05);
}

.audience-card h3 {
    font-size: 1.0625rem;
    font-weight: 700;
    color: #1a1a1a;
}

.audience-card p {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.55;
    color: #6b7280;
}

.audience-link {
    display: inline-block;
    margin-top: 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #f4003a;
    text-decoration: none;
}

.audience-link:hover {
    text-decoration: underline;
}

.stats-grid {
    display: grid;
    gap: 1.25rem;
    margin-top: 2.5rem;
}

@media (min-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.stat-card {
    padding: 2rem 1.5rem;
    text-align: center;
    background: #fff1f4;
    border-radius: 1.5rem;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #f4003a;
    letter-spacing: -0.03em;
}

.stat-label {
    margin-top: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    color: #6b7280;
}

.testimonials-grid {
    display: grid;
    gap: 1.25rem;
    margin-top: 2.5rem;
}

@media (min-width: 900px) {
    .testimonials-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.testimonial-card {
    padding: 1.5rem;
    background: #fff;
    border-radius: 1.25rem;
    border: 1px solid #e5e7eb;
}

.testimonial-text {
    font-size: 0.875rem;
    line-height: 1.6;
    color: #374151;
    font-style: italic;
}

.testimonial-card footer {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.testimonial-card strong {
    display: block;
    font-size: 0.875rem;
    color: #1a1a1a;
}

.testimonial-card span {
    font-size: 0.75rem;
    color: #9ca3af;
}

.pricing-compare {
    margin-top: 1.5rem;
    padding: 0;
    list-style: none;
    max-width: 28rem;
}

.pricing-compare-centered {
    margin-left: auto;
    margin-right: auto;
}

.pricing-plans-grid {
    display: grid;
    gap: 1.25rem;
    margin-top: 2.5rem;
    align-items: stretch;
}

@media (min-width: 640px) {
    .pricing-plans-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .pricing-plans-grid {
        grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
    }
}

.price-box-featured {
    border-width: 2px;
    border-color: #f4003a;
    box-shadow: 0 12px 40px rgb(244 0 58 / 0.12);
    transform: scale(1.02);
}

.price-badge {
    display: inline-block;
    margin-bottom: 0.5rem;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    background: #f4003a;
    color: #fff;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.price-box:not(.price-box-featured) {
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 20px rgb(0 0 0 / 0.06);
}

.pricing-compare li {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.9375rem;
}

.text-muted {
    color: #9ca3af;
}

.text-brand {
    color: #f4003a;
    font-weight: 700;
}

.price-box {
    padding: 1.75rem;
    background: #fff;
    border-radius: 1.5rem;
}

.price-tag {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #6b7280;
}

.price-main {
    margin-top: 0.25rem;
    font-size: 2.75rem;
    font-weight: 800;
    color: #1a1a1a;
    letter-spacing: -0.03em;
}

.price-currency {
    font-size: 1.25rem;
    vertical-align: top;
}

.price-period {
    font-size: 1rem;
    font-weight: 500;
    color: #6b7280;
}

.price-list {
    margin: 1.25rem 0;
    padding: 0;
    list-style: none;
}

.price-list li {
    position: relative;
    padding: 0.35rem 0 0.35rem 1.25rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.price-list li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #00a650;
    font-weight: 700;
}

.price-note {
    margin-top: 0.75rem;
    text-align: center;
    font-size: 0.75rem;
    color: #9ca3af;
}

.contact-row {
    display: grid;
    gap: 2rem;
    align-items: start;
}

@media (min-width: 900px) {
    .contact-row {
        grid-template-columns: 0.9fr 1.1fr;
    }
}

.contact-box {
    padding: 1.5rem;
    background: #fff;
    border-radius: 1.25rem;
    box-shadow: 0 8px 32px rgb(0 0 0 / 0.15);
}

.form-field {
    margin-bottom: 1rem;
}

.form-row {
    display: grid;
    gap: 1rem;
}

@media (min-width: 540px) {
    .form-row {
        grid-template-columns: 1fr 1fr;
    }
}

.form-field label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
}

.optional {
    font-weight: 400;
    color: #9ca3af;
}

.form-field input,
.form-field textarea {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 0.65rem 0.85rem;
    font-size: 0.9375rem;
    color: #1a1a1a;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.form-field input:focus,
.form-field textarea:focus {
    border-color: #f4003a;
    box-shadow: 0 0 0 3px rgb(244 0 58 / 0.12);
}

.form-error {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #dc2626;
}

.btn-red {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: #f4003a;
    padding: 0.85rem 1.5rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #fff;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: background 0.15s, transform 0.15s;
    box-shadow: 0 4px 14px rgb(244 0 58 / 0.35);
}

.btn-red:hover:not(:disabled) {
    background: #d10032;
}

.btn-red:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 2px solid #e5e7eb;
    background: #fff;
    padding: 0.8rem 1.35rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    text-decoration: none;
    transition: border-color 0.15s, background 0.15s;
}

.btn-outline:hover {
    border-color: #f4003a;
    color: #f4003a;
}

.btn-block {
    width: 100%;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
