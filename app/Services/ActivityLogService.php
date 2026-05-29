<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public const ACTION_LABELS = [
        'order.created' => 'Pedido criado',
        'order.approved' => 'Pedido aprovado',
        'order.rejected' => 'Pedido recusado',
        'order.cancelled' => 'Pedido cancelado',
        'order.status_changed' => 'Status alterado',
        'order.payment_marked_paid' => 'Pagamento registrado',
        'order.payment_reverted' => 'Pagamento desfeito',
        'order.status_corrected' => 'Status corrigido',
        'order.delivery_confirmed' => 'Entrega confirmada',
        'order.delivery_updated' => 'Entrega atualizada',
        'support.created' => 'Solicitação aberta',
        'support.note_updated' => 'Resposta do suporte',
        'support.status_changed' => 'Status do suporte alterado',
        'support.closed' => 'Solicitação encerrada',
        'support.reopened' => 'Solicitação reaberta',
        'branch.orders_status_changed' => 'Status do cardápio alterado',
        'branch.auto_accept_changed' => 'Aprovação automática alterada',
        'order.motoboy_assigned' => 'Entregador atribuído',
        'order.motoboy_accepted' => 'Entregador aceitou',
        'order.motoboy_rejected' => 'Entregador recusou',
        'motoboy.reported' => 'Denúncia de entregador',
        'motoboy.report_handled' => 'Denúncia tratada',
        'motoboy.deactivated' => 'Entregador desativado',
        'webhook.delivery_status' => 'Webhook de entrega',
        'order.payment_refunded' => 'Pagamento estornado',
        'order.return_processed' => 'Devolução processada',
        'product.created' => 'Produto criado',
        'product.updated' => 'Produto atualizado',
        'product.deleted' => 'Produto removido',
        'category.created' => 'Categoria criada',
        'category.updated' => 'Categoria atualizada',
        'category.deleted' => 'Categoria removida',
        'combo.created' => 'Combo criado',
        'combo.updated' => 'Combo atualizado',
        'combo.deleted' => 'Combo removido',
        'coupon.created' => 'Cupom criado',
        'coupon.updated' => 'Cupom atualizado',
        'coupon.deleted' => 'Cupom removido',
        'user.created' => 'Usuário criado',
        'user.updated' => 'Usuário atualizado',
        'user.deleted' => 'Usuário removido',
    ];

    public function log(
        Model $subject,
        string $action,
        string $description,
        array $properties = [],
        ?string $origin = null,
    ): ActivityLog {
        $tenantId = $this->resolveTenantId($subject);
        [$userId, $customerId, $label] = $this->resolveActor();

        return ActivityLog::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'description' => $description,
            'properties' => $properties ?: null,
            'actor_user_id' => $userId,
            'actor_customer_id' => $customerId,
            'actor_label' => $label,
            'origin' => $origin ?? $this->defaultOrigin($userId, $customerId),
            'ip_address' => request()->ip(),
        ]);
    }

    public function actionLabel(string $action): string
    {
        return self::ACTION_LABELS[$action] ?? $action;
    }

    public function formatForUi(ActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'action' => $log->action,
            'action_label' => $this->actionLabel($log->action),
            'description' => $log->description,
            'properties' => $log->properties,
            'actor_name' => $log->actor_label
                ?? $log->actorUser?->name
                ?? $log->actorCustomer?->name
                ?? 'Sistema',
            'origin' => $log->origin,
            'created_at' => $log->created_at?->format('d/m/Y H:i:s'),
            'created_at_iso' => $log->created_at?->toIso8601String(),
        ];
    }

    protected function resolveTenantId(Model $subject): ?int
    {
        if (isset($subject->tenant_id)) {
            return (int) $subject->tenant_id;
        }

        return TenantContext::get()?->id;
    }

    /**
     * @return array{0: ?int, 1: ?int, 2: ?string}
     */
    protected function resolveActor(): array
    {
        $user = Auth::user();
        if ($user instanceof User) {
            return [$user->id, null, $user->name];
        }

        $customer = Auth::guard('customer')->user();
        if ($customer instanceof Customer) {
            return [null, $customer->id, $customer->name];
        }

        return [null, null, null];
    }

    protected function defaultOrigin(?int $userId, ?int $customerId): string
    {
        if ($userId) {
            return 'admin';
        }

        if ($customerId) {
            return 'customer';
        }

        return 'system';
    }
}
