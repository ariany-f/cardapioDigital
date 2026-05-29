<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\BranchAccess;
use Inertia\Inertia;
use Inertia\Response;

class OrderSettingsController extends Controller
{
    public function index(): Response
    {
        $branches = BranchAccess::branchesForUser(auth()->user())
            ->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'is_active' => (bool) $branch->is_active,
                'auto_accept_orders' => (bool) $branch->auto_accept_orders,
            ]);

        return Inertia::render('Admin/Orders/Settings', [
            'branches' => $branches,
        ]);
    }
}
