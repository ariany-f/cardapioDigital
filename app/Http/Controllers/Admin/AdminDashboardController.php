<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Services\BranchHoursService;
use App\Support\BranchAccess;
use App\Support\TenantContext;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(BranchHoursService $hours): Response
    {
        $tenant = TenantContext::get();

        $user = auth()->user();

        $branches = BranchAccess::branchesForUser($user)
            ->where('is_active', true)
            ->values()
            ->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'auto_accept_orders' => (bool) $branch->auto_accept_orders,
                ...$hours->adminStatusPayload($branch),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'branches' => $branches,
            'stats' => [
                'orders_today' => BranchAccess::scopeBranchColumn(Order::query(), 'branch_id', $user)
                    ->whereDate('created_at', today())->count(),
                'orders_pending' => BranchAccess::scopeBranchColumn(Order::query(), 'branch_id', $user)
                    ->where('status', 'pending_approval')->count(),
                'orders_preparing' => BranchAccess::scopeBranchColumn(Order::query(), 'branch_id', $user)
                    ->where('status', 'preparing')->count(),
                'revenue_today' => BranchAccess::scopeBranchColumn(Order::query(), 'branch_id', $user)
                    ->whereDate('created_at', today())
                    ->whereNotIn('status', ['cancelled', 'rejected'])
                    ->sum('total'),
            ],
            'pendingOrders' => BranchAccess::scopeBranchColumn(Order::query(), 'branch_id', $user)
                ->with('branch:id,name')
                ->where('status', 'pending_approval')
                ->latest()
                ->limit(10)
                ->get(['id', 'order_number', 'status', 'total', 'branch_id', 'guest_name', 'created_at']),
            'recentOrders' => BranchAccess::scopeBranchColumn(Order::query(), 'branch_id', $user)
                ->with('branch:id,name')
                ->latest()
                ->limit(8)
                ->get(['id', 'order_number', 'status', 'total', 'branch_id', 'created_at']),
            'tenantName' => $tenant->name,
        ]);
    }

}
