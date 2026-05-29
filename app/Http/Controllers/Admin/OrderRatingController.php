<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderRating;
use App\Services\OrderRatingService;
use App\Support\BranchAccess;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderRatingController extends Controller
{
    public function __construct(
        protected OrderRatingService $ratings,
    ) {}

    public function index(Request $request): Response
    {
        $tenant = TenantContext::get();

        $ratings = OrderRating::query()
            ->with(['order', 'branch', 'motoboy', 'customer', 'moderatedBy'])
            ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id))
            ->when(true, fn ($q) => BranchAccess::scopeBranchColumn($q, 'branch_id', $request->user()))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->type === 'delivery', fn ($q) => $q->whereNotNull('delivery_rating'))
            ->when($request->type === 'order', fn ($q) => $q->whereNotNull('rating'))
            ->when($request->motoboy_id, fn ($q, $id) => $q->where('motoboy_id', $id))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (OrderRating $r) => $this->withTenantName($r));

        return Inertia::render('Admin/Ratings/Index', [
            'ratings' => $ratings,
            'filters' => $request->only(['status', 'type', 'motoboy_id']),
            'summary' => $tenant ? $this->ratings->tenantAverages($tenant->id) : null,
            'motoboyStats' => $tenant ? $this->ratings->motoboyAverages($tenant->id) : [],
            'canModerateRestaurant' => false,
        ]);
    }

    public function updateStatus(Request $request, string $tenant, OrderRating $rating): RedirectResponse
    {
        $this->authorizeRating($request, $rating);

        $data = $request->validate([
            'status' => ['required', 'in:approved,hidden'],
        ]);

        $this->ratings->setStatus($rating, $data['status'], $request->user());

        return back()->with('success', $data['status'] === 'hidden'
            ? 'Avaliação ocultada.'
            : 'Avaliação publicada novamente.');
    }

    protected function authorizeRating(Request $request, OrderRating $rating): void
    {
        $tenant = TenantContext::get();
        if (! $tenant || $rating->tenant_id !== $tenant->id) {
            abort(404);
        }

        BranchAccess::assertCanAccessBranch($request->user(), (int) $rating->branch_id);
    }

    protected function withTenantName(OrderRating $rating): array
    {
        return $rating->toAdminPayload();
    }
}
