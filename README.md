# App Cardápio — Multitenant

Cardápio digital multitenant (Laravel 12 + Vue 3 + Inertia + MySQL).

**Desenvolvimento:** [Guia de mapeamento por módulo](docs/DEVELOPMENT.md) — onde alterar rotas, controllers, services e páginas Vue para cada funcionalidade.

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8 (Laragon/XAMPP)

## Banco de dados

```sql
CREATE DATABASE IF NOT EXISTS appcardapio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Configure `.env` (copie de `.env.example`):

- `DB_DATABASE=appcardapio`
- `DB_USERNAME=root`
- `DB_PASSWORD=` (vazio)
- `APP_URL=http://127.0.0.1:4600`

## Instalação

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan db:seed --class=DemoSeeder
php artisan storage:link
npm install
npm run build
```

## Rodar em desenvolvimento

```bash
composer dev
```

Sobe Laravel (`http://127.0.0.1:4600`) + Vite. No Windows não usamos `pail` nem `queue:listen` no script (fila `sync` no `.env`; logs de e-mail em `storage/logs/mail.log`).

Ou em dois terminais:

```bash
php artisan serve --host=127.0.0.1 --port=4600
npm run dev
```

Acesse: http://127.0.0.1:4600

## Credenciais

| Papel | URL | E-mail | Senha |
|-------|-----|--------|-------|
| Superadmin | `/platform` | `admin@admin.com.br` | `Mudar123@` |

O superadmin pode abrir o **painel completo** de qualquer restaurante (pedidos, KDS, entregas, combos, etc.) em `/{slug}/admin` — por exemplo http://127.0.0.1:4600/acme/admin — ou pelo botão **Painel** na lista de restaurantes em `/platform/tenants`.
| Admin ACME (demo) | `/acme/admin` | `admin@acme.test` | `password` |
| Gerente demo | `/acme/admin` | `gerente@acme.test` | `password` |
| Cliente demo | `/acme/conta` | `cliente@demo.test` | `password` |

## URLs demo (após DemoSeeder)

- Marca (sem lista de filiais): http://127.0.0.1:4600/acme
- **Cardápio público da filial** (link para divulgar): http://127.0.0.1:4600/acme/centro
- **Marca (lista de filiais)**: http://127.0.0.1:4600/acme
- **Mesa com QR (demo)**: http://127.0.0.1:4600/acme/centro/mesa/mesa01demo
- `/acme/centro/cardapio` redireciona para o link acima
- Pedido: http://127.0.0.1:4600/acme/pedido/ACME-0001
- Admin mesas/QR: http://127.0.0.1:4600/acme/admin/tables
- Conta do cliente: http://127.0.0.1:4600/acme/conta
- Ajuda / suporte: http://127.0.0.1:4600/acme/ajuda
- Configurações (admin): http://127.0.0.1:4600/acme/admin/settings
- Usuários do tenant (RBAC): http://127.0.0.1:4600/acme/admin/users
- **Combos**: http://127.0.0.1:4600/acme/admin/combos

Fotos demo dos produtos ficam em `public/images/demo/products/` e são aplicadas pelo `DemoSeeder` via `DemoProductImages`. Rode `php artisan db:seed --class=DemoSeeder` para atualizar `image_path` nos produtos existentes.
- **Banners**: http://127.0.0.1:4600/acme/admin/banners
- **Motoboys**: http://127.0.0.1:4600/acme/admin/motoboys
- **Zonas de entrega** (por filial): http://127.0.0.1:4600/acme/admin/branches → link **Zonas** na filial Centro

## E-mail (dev)

Em local, e-mails **não são enviados** — registrados em `storage/logs/mail.log` (`MAIL_SEND_IN_LOCAL=false`).

## Imagens

- Local: `storage/app/public` (`FILESYSTEM_DISK=local`)
- Produção: configure `FILESYSTEM_DISK=s3` e chaves AWS no `.env`

## Seeders

- `php artisan db:seed` — superadmin + plano + permissões
- `php artisan db:seed --class=DemoSeeder` — restaurante ACME completo
- Ou `SEED_DEMO=true` no `.env` antes do `db:seed`
