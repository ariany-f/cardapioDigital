<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = Order::withoutGlobalScopes()
            ->with(['tenant:id,name,slug', 'branch:id,name'])
            ->when($request->tenant_id, fn ($q, $id) => $q->where('tenant_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->q, function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('order_number', 'like', "%{$term}%")
                        ->orWhere('guest_name', 'like', "%{$term}%")
                        ->orWhere('guest_phone', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Platform/Orders/Index', [
            'orders' => $orders,
            'tenants' => Tenant::orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => $request->only('tenant_id', 'status', 'q'),
        ]);
    }
}
