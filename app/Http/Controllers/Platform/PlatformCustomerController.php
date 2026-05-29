<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformCustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $customers = Customer::query()
            ->when($request->q, function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->withCount('orders')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Platform/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only('q'),
        ]);
    }

    public function show(Customer $customer): Response
    {
        $orders = Order::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->with('tenant:id,name,slug', 'branch:id,name')
            ->latest()
            ->limit(50)
            ->get();

        return Inertia::render('Platform/Customers/Show', [
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }
}
