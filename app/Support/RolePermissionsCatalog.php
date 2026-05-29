<?php

namespace App\Support;

use Spatie\Permission\Models\Role;

class RolePermissionsCatalog
{
    /**
     * @return array<string, array{label: string, areas: list<string>, actions: list<string>}>
     */
    public static function permissionMeta(): array
    {
        return [
            'products.manage' => [
                'label' => 'Gerenciar cardápio',
                'areas' => ['Categorias', 'Produtos', 'Combos', 'Banners', 'Idiomas'],
                'actions' => [],
            ],
            'orders.view' => [
                'label' => 'Ver pedidos',
                'areas' => ['Painel', 'Pedidos', 'Registro de atividades'],
                'actions' => [],
            ],
            'orders.accept' => [
                'label' => 'Aceitar e aprovar pedidos',
                'areas' => ['Aprovação de pedidos'],
                'actions' => ['Confirmar, recusar e alterar status de pedidos'],
            ],
            'orders.cancel' => [
                'label' => 'Cancelar pedidos',
                'areas' => [],
                'actions' => ['Cancelar pedidos em andamento'],
            ],
            'orders.print' => [
                'label' => 'Imprimir pedidos',
                'areas' => [],
                'actions' => ['Imprimir comandas e recibos'],
            ],
            'orders.pos' => [
                'label' => 'Ponto de venda (PDV)',
                'areas' => ['PDV'],
                'actions' => ['Lançar vendas no balcão'],
            ],
            'deliveries.update' => [
                'label' => 'Entregas e integrações',
                'areas' => ['Entregadores', 'Denúncias de entrega', 'Webhooks'],
                'actions' => ['Atribuir motoboy e atualizar entrega'],
            ],
            'coupons.manage' => [
                'label' => 'Cupons',
                'areas' => ['Cupons'],
                'actions' => [],
            ],
            'reports.view' => [
                'label' => 'Relatórios',
                'areas' => ['Relatórios'],
                'actions' => ['Exportar e analisar vendas'],
            ],
            'kds.access' => [
                'label' => 'Cozinha (KDS)',
                'areas' => ['KDS'],
                'actions' => [],
            ],
            'users.manage' => [
                'label' => 'Administração',
                'areas' => ['Configurações', 'Usuários'],
                'actions' => ['Criar, editar e remover usuários da equipe'],
            ],
            'requests.view' => [
                'label' => 'Suporte (visualizar)',
                'areas' => ['Suporte'],
                'actions' => ['Ver solicitações de clientes'],
            ],
            'requests.close' => [
                'label' => 'Suporte (atender)',
                'areas' => [],
                'actions' => ['Responder, encerrar e processar devoluções'],
            ],
            'branches.manage' => [
                'label' => 'Filiais e salão',
                'areas' => ['Filiais', 'Mesas'],
                'actions' => [],
            ],
            'chat.access' => [
                'label' => 'Chat com clientes',
                'areas' => ['Chat'],
                'actions' => [],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function roleSummaries(): array
    {
        return [
            'tenant_admin' => 'Acesso completo ao painel do restaurante, incluindo equipe, configurações e todas as filiais.',
            'manager' => 'Gerencia operação, cardápio, filiais e relatórios. Não gerencia usuários nem configurações globais.',
            'operator' => 'Focado no dia a dia: pedidos, cozinha, PDV, chat e entregas. Sem alterar cardápio ou relatórios gerenciais.',
            'viewer' => 'Somente leitura: acompanha pedidos, relatórios e suporte, sem alterar nada.',
            'branch_staff' => 'Operação na cozinha e no balcão, limitada às filiais que você escolher abaixo.',
        ];
    }

    /**
     * @return array{
     *     summary: string,
     *     areas: list<string>,
     *     actions: list<string>,
     *     permissions: list<array{key: string, label: string}>,
     *     branch_note: string
     * }
     */
    public static function describe(string $roleName): array
    {
        $role = Role::query()->where('name', $roleName)->with('permissions')->first();
        $permissionNames = $role
            ? $role->permissions->pluck('name')->sort()->values()->all()
            : [];

        $meta = self::permissionMeta();
        $areas = [];
        $actions = [];
        $permissions = [];

        foreach ($permissionNames as $key) {
            if (! isset($meta[$key])) {
                continue;
            }
            $entry = $meta[$key];
            $permissions[] = ['key' => $key, 'label' => $entry['label']];
            foreach ($entry['areas'] as $area) {
                $areas[$area] = true;
            }
            foreach ($entry['actions'] as $action) {
                $actions[$action] = true;
            }
        }

        $areaList = array_keys($areas);
        $actionList = array_keys($actions);
        sort($areaList, SORT_NATURAL | SORT_FLAG_CASE);
        sort($actionList, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'summary' => self::roleSummaries()[$roleName] ?? '',
            'areas' => $areaList,
            'actions' => $actionList,
            'permissions' => $permissions,
            'branch_note' => self::branchNoteFor($roleName),
        ];
    }

    /**
     * @return array<string, array{summary: string, areas: list<string>, actions: list<string>, permissions: list<array{key: string, label: string}>, branch_note: string}>
     */
    public static function allForTenantRoles(): array
    {
        $roles = ['tenant_admin', 'manager', 'operator', 'viewer', 'branch_staff'];

        $result = [];
        foreach ($roles as $role) {
            $result[$role] = self::describe($role);
        }

        return $result;
    }

    protected static function branchNoteFor(string $roleName): string
    {
        return match ($roleName) {
            'tenant_admin' => 'Sempre enxerga pedidos e telas de todas as filiais.',
            'branch_staff' => 'Obrigatório marcar filiais específicas — só vê pedidos, KDS e chat dessas unidades.',
            default => 'Por padrão vê todas as filiais; desmarque “Acesso a todas as filiais” para restringir.',
        };
    }
}
