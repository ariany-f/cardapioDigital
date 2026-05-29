<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\ActivityLogService;
use App\Support\BranchAccess;
use App\Services\BranchHoursService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchOrdersStatusController extends Controller
{
    public function update(Request $request, string $tenant, Branch $branch, BranchHoursService $hours, ActivityLogService $logger): RedirectResponse
    {
        BranchAccess::assertCanAccessBranch($request->user(), (int) $branch->id);

        $data = $request->validate([
            'orders_status_override' => ['nullable', Rule::in(['open', 'closed'])],
            'auto_accept_orders' => ['sometimes', 'boolean'],
        ]);

        $updates = [];
        if (array_key_exists('orders_status_override', $data)) {
            $updates['orders_status_override'] = $data['orders_status_override'];
        }
        if (array_key_exists('auto_accept_orders', $data)) {
            $updates['auto_accept_orders'] = (bool) $data['auto_accept_orders'];
        }

        if ($updates !== []) {
            $branch->update($updates);
        }

        $branch->refresh();

        if (array_key_exists('auto_accept_orders', $data)) {
            $logger->log(
                $branch,
                'branch.auto_accept_changed',
                $branch->auto_accept_orders
                    ? 'Aprovação automática de pedidos ativada'
                    : 'Aprovação automática de pedidos desativada',
                ['auto_accept_orders' => $branch->auto_accept_orders],
                'admin',
            );
        }

        if (array_key_exists('orders_status_override', $data)) {
            $logger->log(
                $branch,
                'branch.orders_status_changed',
                match ($branch->orders_status_override) {
                    'open' => 'Cardápio aberto manualmente',
                    'closed' => 'Cardápio fechado manualmente',
                    default => 'Cardápio seguindo horário de funcionamento',
                },
                ['orders_status_override' => $branch->orders_status_override],
                'admin',
            );
        }

        if (array_key_exists('auto_accept_orders', $data) && ! array_key_exists('orders_status_override', $data)) {
            $message = $branch->auto_accept_orders
                ? "{$branch->name}: novos pedidos do cardápio serão aprovados automaticamente."
                : "{$branch->name}: novos pedidos aguardarão confirmação manual.";

            return back()->with('success', $message);
        }

        $status = $hours->adminStatusPayload($branch);

        $message = match ($branch->orders_status_override) {
            'open' => "{$branch->name}: cardápio aberto manualmente.",
            'closed' => "{$branch->name}: cardápio fechado manualmente.",
            default => $status['is_open_by_schedule']
                ? "{$branch->name}: seguindo horário de funcionamento (aberto)."
                : "{$branch->name}: seguindo horário de funcionamento (fechado).",
        };

        return back()->with('success', $message);
    }
}
