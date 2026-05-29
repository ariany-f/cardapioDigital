<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ScopesOrdersToUserBranches;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class KdsController extends Controller
{
    use ScopesOrdersToUserBranches;

    public function index(): Response
    {
        $orders = $this->ordersQuery()
            ->whereIn('status', ['pending_approval', 'confirmed', 'preparing', 'ready'])
            ->with(['items', 'branch:id,name', 'table:id,name'])
            ->orderBy('created_at')
            ->get();

        return Inertia::render('Admin/Kds', [
            'orders' => $orders,
        ]);
    }
}
