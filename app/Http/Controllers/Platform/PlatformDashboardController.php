<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Inertia\Inertia;
use Inertia\Response;

class PlatformDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Platform/Dashboard', [
            'stats' => [
                'tenants_total' => Tenant::count(),
                'tenants_active' => Tenant::where('status', 'active')->count(),
                'tenants_suspended' => Tenant::where('status', 'suspended')->count(),
                'subscriptions_overdue' => TenantSubscription::where('status', 'active')
                    ->whereIn('payment_status', ['overdue', 'pending'])
                    ->count(),
            ],
            'recentTenants' => Tenant::query()
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'slug', 'status', 'created_at']),
        ]);
    }
}
