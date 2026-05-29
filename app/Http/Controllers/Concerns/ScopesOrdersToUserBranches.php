<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Order;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;

trait ScopesOrdersToUserBranches
{
    protected function ordersQuery(): Builder
    {
        return BranchAccess::scopeBranchColumn(Order::query(), 'branch_id', auth()->user());
    }

    protected function assertOrderBranchAccess(Order $order): void
    {
        BranchAccess::assertCanAccessBranch(auth()->user(), (int) $order->branch_id);
    }
}
