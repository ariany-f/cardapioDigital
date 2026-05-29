# Guia de desenvolvimento — App Cardápio

Documentação para quem vai manter ou estender o projeto: **onde mexer** para alterar cada área do sistema.

Stack: **Laravel 12**, **Vue 3**, **Inertia.js**, **Tailwind**, **MySQL**, multitenancy por **slug** na URL.

---

## Índice

1. [Visão da arquitetura](#1-visão-da-arquitetura)
2. [URLs e superfícies do app](#2-urls-e-superfícies-do-app)
3. [Multitenancy — o que todo dev precisa saber](#3-multitenancy--o-que-todo-dev-precisa-saber)
4. [Mapeamento por módulo](#4-mapeamento-por-módulo)
5. [Camadas do backend](#5-camadas-do-backend)
6. [Frontend (Inertia + Vue)](#6-frontend-inertia--vue)
7. [Feature flags e módulos opcionais](#7-feature-flags-e-módulos-opcionais)
8. [Autenticação e permissões](#8-autenticação-e-permissões)
9. [Testes](#9-testes)
10. [Traduções e textos](#10-traduções-e-textos)
11. [Tarefas comuns — atalhos](#11-tarefas-comuns--atalhos)

---

## 1. Visão da arquitetura

```
┌─────────────────────────────────────────────────────────────────┐
│  Browser                                                         │
└────────────┬────────────────────────────────────────────────────┘
             │ Inertia (JSON + Vue pages)
┌────────────▼────────────────────────────────────────────────────┐
│  routes/web.php → platform.php | tenant.php | auth.php | conta.php │
│  Middleware: ResolveTenant → HandleInertiaRequests → ...           │
│  Controllers (thin) → Services (regras) → Models (dados)           │
└────────────┬────────────────────────────────────────────────────┘
             │
┌────────────▼────────────────────────────────────────────────────┐
│  MySQL — quase tudo com tenant_id (trait BelongsToTenant)        │
└─────────────────────────────────────────────────────────────────┘
```

**Regra prática:** lógica de negócio em `app/Services/` ou `app/Support/`; controllers orquestram HTTP; Vue só apresenta e valida UX.

---

## 2. URLs e superfícies do app

| Superfície | Prefixo de URL | Rotas | Layout Vue | Quem usa |
|------------|----------------|-------|------------|----------|
| Landing marketing | `/` | `routes/web.php` | `MarketingLayout` | Visitante |
| Superadmin | `/platform/*` | `routes/platform.php` | `PlatformLayout` (sidebar índigo) | `is_platform_user` |
| Restaurante (admin) | `/{tenant}/admin/*` | `routes/tenant.php` | `AdminLayout` (sidebar marca) | Users do tenant + superadmin |
| Cardápio público | `/{tenant}`, `/{tenant}/{branch}` | `routes/tenant.php` | `PublicLayout` / `PublicMenuLayout` | Cliente / visitante |
| Conta cliente (tenant) | `/{tenant}/conta/*` | `routes/tenant.php` | páginas em `Pages/Conta/` | `auth:customer` |
| Conta global | `/conta/*` | `routes/conta.php` | idem | Cliente multi-loja |
| App entregador | `/{tenant}/entregador/*` | `routes/tenant.php` | `Pages/Entregador/` | `auth:motoboy` |
| API webhook | `POST /api/webhooks/delivery` | `routes/api.php` | — | Integrações externas |
| Login staff | `/login` | `routes/auth.php` | `AuthLayout` | Admin / superadmin |

Slugs reservados (não viram tenant): definidos em `routes/web.php` (`Route::pattern('tenant', ...)`).

---

## 3. Multitenancy — o que todo dev precisa saber

| Peça | Arquivo | Função |
|------|---------|--------|
| Resolver tenant pela URL | `app/Http/Middleware/ResolveTenant.php` | Carrega tenant pelo `{tenant}` e preenche `TenantContext` |
| Contexto global | `app/Support/TenantContext.php` | `TenantContext::get()` / `::set()` durante o request |
| Scope automático | `app/Traits/BelongsToTenant.php` | Models filtram por `tenant_id` |
| Tenant suspenso | `app/Http/Middleware/EnsureTenantIsActive.php` | Bloqueia loja inativa |
| Props compartilhados | `app/Http/Middleware/HandleInertiaRequests.php` | `tenant`, `auth`, `translations`, feature flags |
| Config em JSON | `tenants.settings_json` | Checkout visitante, módulos, entrega, etc. |

**Onde guardar config do restaurante:**

- Flags de módulo (motoboys, PDV, KDS): `app/Support/TenantFeatures.php` → chaves em `settings_json`
- Pedidos / checkout: `app/Support/TenantOrderSettings.php`
- Entrega / motoboy auto-aceite: `app/Support/TenantDeliverySettings.php`
- Pagamentos: `app/Support/TenantPaymentSettings.php`
- Plano (limites): `app/Support/TenantPlanFeatures.php` + model `Plan`

---

## 4. Mapeamento por módulo

Use esta tabela como índice. Em cada linha: **comece pelas rotas**, depois controller, service, página Vue e teste.

### 4.1 Plataforma (superadmin)

| O que alterar | Rotas | Controller | Frontend | Testes |
|---------------|-------|------------|----------|--------|
| Lista/CRUD restaurantes | `platform.tenants.*` | `Platform/TenantController` | `Pages/Platform/Tenants/Index.vue`, `TenantFormFields.vue` | `TenantMotoboysFeatureTest`, `PlatformTenant*` |
| Módulos (motoboys/PDV/KDS) | update tenant | `TenantFeatures`, validação em `ValidatesPlatformTenant` | checkbox em `TenantFormFields.vue` | `TenantMotoboysFeatureTest`, `TenantPosFeatureTest`, `TenantKdsFeatureTest` |
| Filiais (platform) | `platform.tenants.branches.*` | `Platform/TenantBranchController` | `Pages/Platform/Tenants/Branches.vue` | `PlatformTenantBranchTest` |
| Catálogo remoto | `platform.tenants.products.*` | `Platform/TenantProductController` | `Pages/Platform/Tenants/Catalog/*` | — |
| Pedidos globais | `platform.orders.*` | `Platform/PlatformOrderController` | `Pages/Platform/Orders/Index.vue` | — |
| Clientes globais | `platform.customers.*` | `Platform/PlatformCustomerController` | `Pages/Platform/Customers/*` | `CustomerGlobalAccountTest` |
| Solicitações landing | `platform.marketing-leads.*` | `Platform/MarketingLeadController` | `Pages/Platform/MarketingLeads/*` | `PlatformMarketingLeadTest` |
| Planos | `platform.plans.*` | `Platform/PlanController` | `Pages/Platform/Plans/Index.vue` | — |
| Pagamentos assinatura | `platform.payments.*` | `Platform/TenantPaymentController` | `Pages/Platform/Payments/*` | — |
| SMTP plataforma | `platform.settings.email` | `Platform/PlatformMailSettingsController` | `Pages/Platform/Settings/Email.vue` | — |
| SEO plataforma / landing | `platform.settings.seo` | `Platform/PlatformSeoSettingsController` | `Pages/Platform/Settings/Seo.vue` | `PlatformMarketingLandingTest` |
| SEO por tenant | `platform.tenants.seo.*` | `Platform/TenantSeoController` | `Pages/Platform/Tenants/Seo.vue` | — |
| Avaliações (moderação) | `platform.ratings.*` | `Platform/OrderRatingController` | `Pages/Platform/Ratings/Index.vue` | — |

Formulários compartilhados superadmin: `resources/js/composables/platformForms.js`.

---

### 4.2 Cardápio público e checkout

| O que alterar | Rotas | Controller | Service / Support | Frontend |
|---------------|-------|------------|-------------------|----------|
| Home da marca (filiais) | `tenant.home` | `Public/HomeController` | — | `Pages/Public/Home.vue` |
| Cardápio da filial | `tenant.branch`, `tenant.menu` | `Public/BranchController`, `MenuController` | `BranchCatalogService` | `Pages/Public/Branch.vue` |
| Carrinho / modal produto | (Inertia na branch) | — | `OrderItemValidationService`, `CouponService` | `Components/Public/MenuCart.vue` |
| Checkout / criar pedido | `tenant.checkout` | `Public/CheckoutController` | `OrderPaymentService`, `StockService` | lógica no `MenuCart.vue` + branch |
| Checkout sem login | settings | `TenantSettingsController` (admin) | `TenantOrderSettings` | `Branch.vue`, `MenuCart.vue` |
| Mesa (QR) | `tenant.table` | `Public/BranchController::showTable` | — | `Branch.vue` |
| Sessão carrinho (local) | — | — | — | `composables/useMenuSession.js` |
| Horário / loja aberta | — | — | `BranchHoursService` | branch + cart |
| Taxa de entrega | — | — | `DeliveryQuoteService` | `useDeliveryAddressLookup.js` |
| Layout header (ajuda, track) | — | — | — | `Layouts/PublicMenuLayout.vue`, `PublicLayout.vue` |

---

### 4.3 Pedidos (admin do restaurante)

| O que alterar | Rotas | Controller | Service | Frontend |
|---------------|-------|------------|---------|----------|
| Lista de pedidos | `tenant.admin.orders.index` | `Admin/OrderController` | `ScopesOrdersToUserBranches` | `Pages/Admin/Orders/Index.vue` |
| Detalhe / status | `tenant.admin.orders.show`, `.status` | `OrderController` | `OrderStatusRecorder`, `RecordsOrderStatus` | `Pages/Admin/Orders/Show.vue` |
| Aceitar / rejeitar | `.accept`, `.reject` | `OrderController` | `ManagesOrderWorkflow` | `Orders/Show.vue` |
| Cancelar | `.cancel` | `OrderController` | — | — |
| Aprovação automática | `tenant.admin.orders.settings` | `Admin/OrderSettingsController` | — | `Pages/Admin/Orders/Settings.vue` |
| Pausar pedidos por filial | `branches.orders-status` | `Admin/BranchOrdersStatusController` | — | — |
| Impressão | `orders.print` | `Admin/PrintOrderController` | — | view blade |
| Notificações status | — | — | `OrderNotificationService` | — |
| Log de atividades | `activity-logs.index` | `Admin/ActivityLogController` | `ActivityLogService` | `Pages/Admin/ActivityLogs/Index.vue` |

Status de pedido (`orders.status`): `confirmed`, `preparing`, `ready`, `out_for_delivery`, `delivered`, `cancelled`, `rejected`.

---

### 4.4 Entregas e motoboys

| O que alterar | Rotas | Controller | Service | Frontend |
|---------------|-------|------------|---------|----------|
| Atribuir motoboy / status entrega | `tenant.admin.orders.delivery` | `Admin/DeliveryManagementController` | **`DeliveryStatusService`** | `Orders/Show.vue` |
| Código de confirmação | `orders.confirm-delivery` | `OrderController` | **`DeliveryConfirmationService`** | `Orders/Show.vue` |
| Cadastro entregadores | `tenant.admin.motoboys.*` | `Admin/MotoboyController` | `MotoboyBranchAccess` | `Pages/Admin/Motoboys/Index.vue` |
| App do entregador | `tenant.entregador.*` | `Entregador/MotoboyDashboardController` | `DeliveryStatusService` | `Pages/Entregador/*` |
| Login entregador | `entregador.login` | `Entregador/MotoboyAuthController` | — | `Entregador/Login.vue` |
| Denúncia (público) | `tenant.track.report-motoboy` | `Public/MotoboyReportController` | — | `TrackOrder.vue` |
| Denúncias (admin) | `motoboy-reports.*` | `Admin/MotoboyReportController` | — | `Pages/Admin/MotoboyReports/Index.vue` |
| Webhook integração | `api/webhooks/delivery` | `Api/DeliveryWebhookController` | `DeliveryStatusService` | — |
| Zonas de entrega | `branches.zones.*` | `Admin/DeliveryZoneController` | — | `Pages/Admin/DeliveryZones/Index.vue` |
| Desativar módulo (bloqueio) | platform update | `TenantFeatures` | query em `motoboyDeliveriesInProgress` | `TenantFormFields.vue` |

Middleware: `motoboys.enabled` → `EnsureMotoboysEnabled.php`.

Status de entrega (`deliveries.status`): `pending`, `assigned`, `picked_up`, `on_route`, `delivered`, `failed`.  
Em andamento (bloqueia desligar módulo): `assigned`, `picked_up`, `on_route` ou atribuição `pending`.

---

### 4.5 Acompanhamento de pedido (cliente)

| O que alterar | Rotas | Controller | Support | Frontend |
|---------------|-------|------------|---------|----------|
| Busca por código/telefone | `tenant.track.lookup` | `Public/GuestOrderLookupController` | `GuestOrderAccess` | `TrackOrderLookup.vue` |
| Página do pedido | `tenant.track` | **`Public/TrackOrderController`** | `GuestOrderAccess`, `DeliveryConfirmationService` | **`TrackOrder.vue`** |
| Polling status | `tenant.track.status` | `TrackOrderController::status` | — | `TrackOrder.vue` |
| Avaliação | `tenant.track.rate` | `TrackOrderController` | `OrderRatingService` | `TrackOrder.vue` |
| E-mail código visitante | — | — | `GuestOrderAccessNotificationService` | `mail/orders/guest-access.blade.php` |

Traduções da tela: `lang/pt_BR.json` (chaves `order.track.*`, `order.delivery_code.*`).

---

### 4.6 KDS (cozinha)

| O que alterar | Rotas | Controller | Frontend | Middleware |
|---------------|-------|------------|----------|------------|
| Painel cozinha | `tenant.admin.kds` | `Admin/KdsController` | `Pages/Admin/Kds.vue` | `kds.enabled`, `plan.feature:kds` |

Alterar regra retirada vs entrega: `KdsController` + `Kds.vue` (ex.: retirada vai direto para `delivered`).

---

### 4.7 PDV (balcão)

| O que alterar | Rotas | Controller | Frontend | Middleware |
|---------------|-------|------------|----------|------------|
| Tela PDV | `tenant.admin.pos` | `Admin/PosController` | `Pages/Admin/Pos.vue` | `pos.enabled`, `plan.feature:pos` |

---

### 4.8 Catálogo (produtos, categorias, combos, banners)

| O que alterar | Rotas | Controller | Concerns | Frontend |
|---------------|-------|------------|----------|----------|
| Produtos | `tenant.admin.products.*` | `Admin/ProductController` | `ValidatesProductCatalog`, `ManagesProductVariations`, `HandlesProductImageUpload` | `Pages/Admin/Products/Index.vue` |
| Categorias | `tenant.admin.categories.*` | `Admin/CategoryController` | — | `Pages/Admin/Categories/Index.vue` |
| Combos | `tenant.admin.combos.*` | `Admin/ComboController` | — | `Pages/Admin/Combos/Index.vue` |
| Banners | `tenant.admin.banners.*` | `Admin/BannerController` | `BannerImageStorage` | `Pages/Admin/Banners/Index.vue` |
| Variações (sync) | — | — | `ProductVariationSyncService` | formulários produto |
| Imagens | — | — | `ProductImageStorage`, `SecureImageUpload` | — |

Dados do cardápio público montados em `BranchCatalogService`.

---

### 4.9 Filiais, mesas e configurações

| O que alterar | Rotas | Controller | Frontend |
|---------------|-------|------------|----------|
| Filiais | `tenant.admin.branches.*` | `Admin/BranchController` | `Pages/Admin/Branches/Index.vue` |
| Capa da filial | — | `HandlesBranchCoverUpload` | `BranchFormFields` (platform) |
| Mesas / QR | `tenant.admin.tables.*` | `Admin/DiningTableController` | `Pages/Admin/Tables/Index.vue` |
| Config geral | `tenant.admin.settings` | `Admin/TenantSettingsController` | `Pages/Admin/Settings/Index.vue` |
| Cupons | `tenant.admin.coupons.*` | `Admin/CouponController` | `Pages/Admin/Coupons/Index.vue` |
| Idiomas do tenant | `tenant.admin.languages.*` | `Admin/LanguageController` | `Pages/Admin/Languages/Index.vue` |
| Usuários / RBAC | `tenant.admin.users.*` | `Admin/UserController` | `Pages/Admin/Users/Index.vue` |
| Relatórios | `tenant.admin.reports.*` | `Admin/ReportController` | `Pages/Admin/Reports/Index.vue` |

---

### 4.10 Chat

| O que alterar | Rotas | Controller | Service | Frontend |
|---------------|-------|------------|---------|----------|
| Widget público | `tenant.chat.*` | `Public/ChatController` | **`ChatService`** | `Components/Public/ChatWidget.vue` |
| Painel admin | `tenant.admin.chat.*` | `Admin/ChatController` | `ChatService` | `Pages/Admin/Chat/Index.vue` |
| Elegibilidade | — | — | **`ChatEligibility`** | `useChatEligibility.js`, `useAdminChatUnread.js` |

---

### 4.11 Clientes (conta)

| O que alterar | Rotas | Controller | Frontend |
|---------------|-------|------------|----------|
| Login/registro tenant | `tenant.conta.*` | `Public/CustomerAuthController` | `Pages/Conta/*` |
| Conta global | `app.conta.*` | `CustomerAuthController` | idem |
| Dashboard pedidos | `tenant.conta.dashboard` | `CustomerAuthController` | `Conta/Dashboard.vue` |
| Repetir pedido | `tenant.conta.orders.repeat` | `CustomerAuthController` | — |

Guard: `customer` (model `App\Models\Customer`).

---

### 4.12 Avaliações

| O que alterar | Rotas | Controller | Service |
|---------------|-------|------------|---------|
| Cliente avalia | `tenant.track.rate` | `TrackOrderController` | `OrderRatingService` |
| Admin modera | `tenant.admin.ratings.*` | `Admin/OrderRatingController` | — |
| Platform modera | `platform.ratings.*` | `Platform/OrderRatingController` | — |

Model: `OrderRating` (dimensões loja + entrega + motoboy).

---

### 4.13 Comunicação plataforma × restaurante × cliente

| Peça | Arquivo | Comportamento |
|------|---------|---------------|
| Textos legais / avisos | `lang/pt_BR/platform.php`, `PlatformCommunicationDisclaimer` | Fonte única dos textos |
| Props Inertia globais | `HandleInertiaRequests` → `communication_disclaimer` | Vue: `CommunicationNotice.vue` |
| Aviso e-mail status | `OrderNotificationService` → `OrderStatusUpdatedMail` | Dispara quando o **restaurante** altera status (admin/KDS/entrega); só informativo |
| E-mail código visitante | `GuestOrderAccessMail` | Inclui aviso de papel da plataforma |
| Formulário ajuda | `SupportRequestController` | Encaminha ao **restaurante** (não é ticket do App Cardápio) |
| Chat | `ChatService` | Canal direto cliente ↔ restaurante |

**Não fazemos:** suporte ao cliente sobre pedido, reembolso automático, garantia de entrega, frota de motoboys, checagem de dados/confiabilidade de entregadores.  
**Fazemos:** cardápio, registro de pedido, encaminhamento de mensagens, alertas automáticos após ações manuais do restaurante.

**Entrega:** o restaurante precisa de logística própria. Módulo de entregadores (`motoboys` no plano + `motoboys_enabled` no tenant) só cadastra equipe do estabelecimento. Textos: `lang/pt_BR/platform.php` → `delivery.*`, componente `DeliveryNotice.vue`.

---

### 4.14 Suporte, marketing e SEO

| O que alterar | Rotas | Controller | Frontend |
|---------------|-------|------------|----------|
| Landing `/` | `marketing.landing` | `Marketing/LandingController` | `Pages/Marketing/Landing.vue` |
| Formulário contato | `marketing.contact` | `LandingController::contact` | landing |
| Leads (admin) | `platform.marketing-leads.*` | `Platform/MarketingLeadController` | `MarketingLeads/*` |
| Ajuda (público) | `tenant.support` | `Public/SupportRequestController` | `Pages/Public/Support.vue` |
| Tickets (admin) | `tenant.admin.requests.*` | `Admin/SupportRequestController` | `Pages/Admin/Support/Index.vue` |
| Devolução | `requests.process-return` | `SupportRequestController` | `ReturnWorkflowService` |
| robots/sitemap | `seo.robots`, `seo.sitemap` | `Public/SeoController` | — |
| SEO | — | `SeoService`, `PlatformMarketing` | — |

---

### 4.14 Autenticação staff

| O que alterar | Arquivo |
|---------------|---------|
| Login Breeze | `routes/auth.php`, `Auth/AuthenticatedSessionController` |
| Registro | `Auth/RegisteredUserController` |
| Redirect após login | `Auth/Concerns/RedirectsAuthenticatedUsers.php` |
| Middleware platform | `EnsurePlatformUser.php` |
| Middleware tenant admin | `EnsureTenantUser.php` |

---

## 5. Camadas do backend

### 5.1 Diretórios principais

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Painel do restaurante
│   │   ├── Platform/       # Superadmin
│   │   ├── Public/         # Cardápio, checkout, track, conta
│   │   ├── Entregador/     # App motoboy
│   │   ├── Marketing/      # Landing
│   │   ├── Api/            # Webhooks
│   │   └── Concerns/       # Traits reutilizáveis entre controllers
│   └── Middleware/
├── Models/                 # Eloquent (+ BelongsToTenant)
├── Services/               # Regras de negócio
├── Support/                # Helpers, settings, feature flags
├── Mail/
└── Traits/
```

### 5.2 Services (quando usar qual)

| Service | Responsabilidade |
|---------|------------------|
| `DeliveryStatusService` | Fluxo entrega, motoboy, sync status pedido |
| `DeliveryConfirmationService` | Código 6 dígitos na entrega |
| `DeliveryQuoteService` | Cálculo taxa por zona/distância |
| `OrderPaymentService` | Pagamento do pedido |
| `OrderStatusRecorder` | Histórico de status |
| `OrderNotificationService` | Labels / notificações de status |
| `OrderItemValidationService` | Valida itens no checkout |
| `OrderRatingService` | Avaliações |
| `BranchCatalogService` | Monta cardápio para filial |
| `BranchHoursService` | Aberto/fechado |
| `ChatService` | Mensagens chat |
| `CouponService` | Cupons |
| `StockService` | Estoque |
| `ActivityLogService` | Auditoria |
| `GuestOrderAccessNotificationService` | E-mail pedido visitante |
| `SeoService` | Meta / sitemap |

### 5.3 Models importantes

| Model | Tabela / notas |
|-------|----------------|
| `Tenant` | Restaurante; `settings_json`, tema, slug |
| `Branch` | Filial; horários, entrega, capa |
| `Product`, `Category`, `Combo`, `Banner` | Catálogo |
| `Order`, `OrderItem` | Pedidos |
| `Delivery` | Entrega + `motoboy_id` |
| `Motoboy` | Entregador |
| `Customer` | Cliente final |
| `User` | Admin loja ou superadmin |
| `Plan`, `TenantSubscription` | Assinatura |
| `ChatConversation`, `ChatMessage` | Chat |
| `MarketingLead` | Lead da landing |
| `OrderRating`, `MotoboyReport` | Pós-venda |

Migrations: `database/migrations/` (schema grande em `2026_05_28_220000_create_appcardapio_schema.php` + migrations incrementais).

---

## 6. Frontend (Inertia + Vue)

### 6.1 Estrutura

```
resources/js/
├── Pages/              # Uma página por rota Inertia (espelha controllers)
│   ├── Admin/
│   ├── Platform/
│   ├── Public/
│   ├── Conta/
│   ├── Entregador/
│   └── Marketing/
├── Layouts/            # Shell (menu lateral, header)
├── Components/         # UI reutilizável (Admin/, Public/, Platform/)
├── composables/        # Lógica Vue compartilhada
└── app.js              # Boot Inertia + Ziggy (route())
```

### 6.2 Layouts

| Layout | Uso |
|--------|-----|
| `AdminLayout.vue` | Menu lateral restaurante (badge “Restaurante”) |
| `PlatformLayout.vue` | Menu lateral superadmin (sidebar escura) |
| `PublicMenuLayout.vue` | Cardápio da filial |
| `PublicLayout.vue` | Home marca / páginas públicas gerais |
| `MarketingLayout.vue` | Landing |
| `AuthLayout.vue` | Login staff |

Estilos admin vs platform: `resources/css/app.css` (`.admin-shell`, `.platform-shell`).

### 6.3 Composables úteis

| Arquivo | Uso |
|---------|-----|
| `usePermissions.js` | `can('orders.view')` no admin |
| `useMenuSession.js` | Carrinho no localStorage |
| `useChatEligibility.js` | Exibir chat no cardápio |
| `useAdminChatUnread.js` | Badge chat no menu admin |
| `platformForms.js` | Forms do superadmin |
| `useDeliveryAddressLookup.js` | CEP / endereço checkout |
| `useI18n.js` | `t('chave')` com `lang/pt_BR.json` |

### 6.4 Props globais (Inertia)

Definidos em `HandleInertiaRequests`: `auth`, `tenant`, `locale`, `translations`, `flash`.

No Vue: `usePage().props.tenant`, `usePage().props.auth.permissions`.

---

## 7. Feature flags e módulos opcionais

Configurados em `tenants.settings_json` via `TenantFeatures`:

| Flag | Chave JSON | Middleware | Menu admin |
|------|------------|------------|------------|
| Entregadores | `motoboys_enabled` | `motoboys.enabled` | Itens motoboy / denúncias |
| PDV | `pos_enabled` | `pos.enabled` | PDV |
| KDS | `kds_enabled` | `kds.enabled` | KDS |

Quem altera: superadmin em `Platform/Tenants` → `TenantFormFields.vue` → `TenantController::update`.

**Regra:** não desativar motoboys com entrega em andamento (`TenantFeatures::hasMotoboyDeliveriesInProgress`).

Plano também limita features: middleware `plan.feature:*` + `TenantPlanFeatures`.

Checkout visitante: `TenantOrderSettings::guestCheckoutEnabled` (admin em Settings, não é módulo desligável no platform).

---

## 8. Autenticação e permissões

| Guard | Model | Rotas típicas |
|-------|-------|---------------|
| `web` | `User` | `/login`, `/{tenant}/admin` |
| `customer` | `Customer` | `/{tenant}/conta`, `/conta` |
| `motoboy` | `Motoboy` | `/{tenant}/entregador` |

Permissões (Spatie): definidas em `PlatformSeeder`, catálogo em `RolePermissionsCatalog.php`.  
Checagem: middleware `permission:*` nas rotas + `usePermissions()` no Vue.

Superadmin (`is_platform_user`): bypass de permissões; acessa qualquer `/{slug}/admin`.

---

## 9. Testes

```
tests/
├── Feature/     # HTTP / fluxos completos (preferir estes)
└── Unit/        # Poucos; lógica isolada
```

| Área | Arquivos de teste |
|------|-------------------|
| Motoboys / módulos | `TenantMotoboysFeatureTest.php`, `TenantPosFeatureTest.php`, `TenantKdsFeatureTest.php` |
| Entrega / código | `DeliveryConfirmationTest.php`, `MotoboyAppTest.php` |
| Checkout visitante | `GuestCheckoutSettingsTest.php` |
| Track / guest | `GuestOrderAccessTest.php` |
| Chat | `ChatTest.php`, `ChatEligibilityTest.php` |
| Platform | `PlatformMarketingLeadTest.php`, `PlatformTenant*` |
| Público | `PublicTenantPageTest.php` |

Rodar módulo específico:

```bash
php artisan test --filter=TenantMotoboysFeatureTest
```

---

## 10. Traduções e textos

| Tipo | Onde |
|------|------|
| UI pública (JSON) | `lang/pt_BR.json` — chaves usadas com `t()` / `useI18n` |
| Validação Laravel | `lang/pt_BR/validation.php` |
| Mensagens de feature | `lang/pt_BR/tenant.php` |
| E-mails | `resources/views/mail/` |
| Atributos de validação platform | `ValidatesPlatformTenant::tenantValidationAttributes()` |

Idiomas do tenant: model `Language` + `Admin/LanguageController` (export/import JSON).

---

## 11. Tarefas comuns — atalhos

| Quero… | Onde começar |
|--------|----------------|
| Nova página no admin | Rota em `routes/tenant.php` → Controller `Admin/` → `Pages/Admin/` → item em `AdminLayout.vue` |
| Nova página platform | `routes/platform.php` → `Platform/` → `Pages/Platform/` → `PlatformLayout.vue` |
| Nova coluna no pedido | Migration → `Order` `$fillable` → controller + `Orders/Show.vue` |
| Mudar fluxo de status | `OrderController`, `RecordsOrderStatus`, `OrderStatusRecorder` |
| Mudar preço no checkout | `CheckoutController`, `OrderItemValidationService`, `MenuCart.vue` |
| Bloquear feature por plano | `TenantPlanFeatures`, middleware `plan.feature`, seed `Plan` |
| Adicionar permissão | `RolePermissionsCatalog`, seeder, rota com `permission:`, menu com `can()` |
| Prop global no Vue | `HandleInertiaRequests::share()` |
| Novo módulo on/off | `TenantFeatures`, middleware `Ensure*Enabled`, checkbox platform, menu `requires*` em `AdminLayout` |
| Integração externa entrega | `Api/DeliveryWebhookController`, tokens em `Admin/WebhookTokenController` |

---

## Referência rápida de comandos

```bash
composer dev                    # Laravel + Vite
php artisan migrate
php artisan db:seed
php artisan db:seed --class=DemoSeeder
php artisan test
npm run dev
npm run build
```

Credenciais e URLs demo: ver [README.md](../README.md).

---

*Última atualização: documento alinhado à estrutura do repositório em maio/2026. Ao adicionar módulos novos, atualize a seção [4. Mapeamento por módulo](#4-mapeamento-por-módulo).*
