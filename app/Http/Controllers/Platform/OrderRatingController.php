<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\OrderRating;
use App\Models\Tenant;
use App\Services\OrderRatingService;
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
        $ratings = OrderRating::query()
            ->with(['order', 'branch', 'motoboy', 'customer', 'moderatedBy', 'tenant:id,name,slug'])
            ->when($request->tenant_id, fn ($q, $id) => $q->where('tenant_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->type === 'restaurant', fn ($q) => $q->whereNotNull('restaurant_rating'))
            ->when($request->type === 'delivery', fn ($q) => $q->whereNotNull('delivery_rating'))
            ->when($request->type === 'order', fn ($q) => $q->whereNotNull('rating'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (OrderRating $r) => [
                ...$r->toAdminPayload(),
                'tenant' => $r->tenant?->only(['id', 'name', 'slug']),
            ]);

        return Inertia::render('Platform/Ratings/Index', [
            'ratings' => $ratings,
            'filters' => $request->only(['tenant_id', 'status', 'type']),
            'tenants' => Tenant::query()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }

    public function updateStatus(Request $request, OrderRating $rating): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,hidden'],
        ]);

        $this->ratings->setStatus($rating, $data['status'], $request->user());

        return back()->with('success', $data['status'] === 'hidden'
            ? 'Avaliação ocultada.'
            : 'Avaliação publicada novamente.');
    }
}
