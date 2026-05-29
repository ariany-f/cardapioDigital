<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\OrderRatingService;
use App\Services\SeoService;
use App\Support\MediaUrl;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        protected OrderRatingService $ratings,
        protected SeoService $seo,
    ) {}

    public function index(): Response|RedirectResponse
    {
        $tenant = TenantContext::get();
        $tenantSlug = $tenant->slug;

        $activeBranches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($activeBranches->count() === 1) {
            return redirect()->route('tenant.branch', [
                'tenant' => $tenantSlug,
                'branch' => $activeBranches->first()->slug,
            ]);
        }

        $branches = $activeBranches->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'city' => $branch->city,
                'public_description' => $branch->public_description,
                'is_open' => $branch->isOpenNow(),
                'cover_url' => MediaUrl::fromPath($branch->cover_image_path),
                'url' => url("/{$tenantSlug}/{$branch->slug}"),
            ]);

        return Inertia::render('Public/Home', [
            'seo' => $this->seo->forTenant($tenant),
            'tenantName' => $tenant->name,
            'tenantDescription' => $tenant->public_description,
            'branches' => $branches,
            'ratingSummary' => $this->ratings->tenantAverages($tenant->id),
        ]);
    }
}
