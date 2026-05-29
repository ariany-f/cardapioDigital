<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DiningTable;
use App\Services\BranchCatalogService;
use App\Services\SeoService;
use App\Support\ChatEligibility;
use App\Support\TenantContext;
use App\Support\TenantPaymentSettings;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function __construct(
        protected BranchCatalogService $catalog,
        protected SeoService $seo,
    ) {}

    public function show(string $tenant, string $branch): Response
    {
        $tenantModel = TenantContext::get();

        $branchModel = Branch::query()
            ->where('slug', $branch)
            ->where('is_active', true)
            ->firstOrFail();

        return $this->renderBranch($tenantModel, $branchModel);
    }

    public function showTable(string $tenant, string $branch, string $token): Response
    {
        $tenantModel = TenantContext::get();

        $branchModel = Branch::query()
            ->where('slug', $branch)
            ->where('is_active', true)
            ->firstOrFail();

        $table = DiningTable::query()
            ->where('branch_id', $branchModel->id)
            ->where('qr_token', $token)
            ->firstOrFail();

        return $this->renderBranch($tenantModel, $branchModel, [
            'table' => [
                'id' => $table->id,
                'name' => $table->name,
            ],
            'defaultOrderType' => 'dine_in',
        ]);
    }

    protected function renderBranch($tenantModel, Branch $branchModel, array $extra = []): Response
    {
        $customer = auth('customer')->user();
        $request = request();

        return Inertia::render('Public/Branch', [
            'seo' => $this->seo->forBranch($tenantModel, $branchModel),
            'branch' => $this->catalog->branchPayload($branchModel),
            'banners' => $this->catalog->bannersFor($branchModel),
            'featured' => $this->catalog->featuredFor($branchModel),
            'combos' => $this->catalog->combosFor($branchModel),
            'categories' => $this->catalog->categoriesFor($branchModel),
            'tenantSlug' => $tenantModel->slug,
            'publicUrl' => route('tenant.branch', [
                'tenant' => $tenantModel->slug,
                'branch' => $branchModel->slug,
            ]),
            'reorderCart' => session('reorder_cart', []),
            'paymentSettings' => TenantPaymentSettings::from($tenantModel),
            'chatAvailable' => ChatEligibility::canStart($branchModel, $customer, $request),
            'chatGuestProfile' => ChatEligibility::guestProfile($branchModel, $request),
            ...$extra,
        ]);
    }
}
