<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Concerns\HandlesBranchCoverUpload;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ValidatesBranch;
use App\Models\Branch;
use App\Models\Tenant;
use App\Services\OrderRatingService;
use App\Support\MediaUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenantBranchController extends Controller
{
    use HandlesBranchCoverUpload;
    use ValidatesBranch;

    public function __construct(
        protected OrderRatingService $ratings,
    ) {}

    public function index(Tenant $tenant): Response
    {
        return $this->branchesPage($tenant);
    }

    public function create(Tenant $tenant): Response
    {
        return $this->branchesPage($tenant, ['creating' => true]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            ...$this->branchRules($tenant->id),
            ...$this->coverImageRules(),
        ]);

        $slug = $data['slug'] ?? Str::slug($data['name']);
        $slug = $this->uniqueSlug($tenant, $slug);

        $branch = Branch::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            ...$this->branchAttributes([
                ...$data,
                'opening_hours' => $this->normalizeOpeningHours($data['opening_hours'] ?? null),
            ], $slug),
        ]);

        $this->storeBranchCover($request, $branch, $tenant->id);

        return redirect()
            ->route('platform.tenants.branches.index', $tenant)
            ->with('success', 'Filial criada.');
    }

    public function edit(Tenant $tenant, Branch $branch): Response
    {
        $this->ensureBranchBelongsToTenant($tenant, $branch);

        return $this->branchesPage($tenant, ['editingBranch' => $this->branchPayload($tenant, $branch)]);
    }

    public function update(Request $request, Tenant $tenant, Branch $branch): RedirectResponse
    {
        $this->ensureBranchBelongsToTenant($tenant, $branch);

        $data = $request->validate([
            ...$this->branchRules($tenant->id, $branch),
            ...$this->coverImageRules(),
        ]);

        $slug = $data['slug'] ?? $branch->slug;

        $branch->update($this->branchAttributes([
            ...$data,
            'opening_hours' => $this->normalizeOpeningHours($data['opening_hours'] ?? null),
        ], $slug));

        $this->storeBranchCover($request, $branch, $tenant->id);

        return redirect()
            ->route('platform.tenants.branches.edit', [$tenant, $branch])
            ->with('success', 'Filial atualizada.');
    }

    public function destroy(Tenant $tenant, Branch $branch): RedirectResponse
    {
        $this->ensureBranchBelongsToTenant($tenant, $branch);

        $branch->delete();

        return redirect()
            ->route('platform.tenants.branches.index', $tenant)
            ->with('success', 'Filial removida.');
    }

    protected function branchesPage(Tenant $tenant, array $extra = []): Response
    {
        $branchModels = Branch::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $averages = $this->ratings->branchAveragesForMany(
            $tenant->id,
            $branchModels->pluck('id')->all(),
        );

        $branches = $branchModels->map(
            fn (Branch $branch) => $this->branchPayload(
                $tenant,
                $branch,
                $averages[$branch->id] ?? null,
            ),
        );

        return Inertia::render('Platform/Tenants/Branches', [
            'tenant' => [
                ...$tenant->only('id', 'name', 'slug', 'status'),
                'rating_summary' => $this->ratings->tenantAverages($tenant->id),
            ],
            'branches' => $branches,
            ...$extra,
        ]);
    }

    /**
     * @param  array{restaurant: ?float, order: ?float, delivery: ?float, count: int}|null  $ratingSummary
     */
    protected function branchPayload(Tenant $tenant, Branch $branch, ?array $ratingSummary = null): array
    {
        return [
            ...$branch->only([
                'id', 'name', 'slug', 'phone', 'is_active',
                'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'postal_code',
                'latitude', 'longitude', 'opening_hours', 'public_description',
                'pickup_available', 'delivery_available', 'delivery_radius_km',
                'minimum_order_amount', 'packaging_fee_default', 'default_prep_time_minutes', 'delivery_time_minutes',
                'auto_accept_orders', 'allow_scheduled_orders', 'auto_print_on_new_order',
                'notification_email', 'print_format', 'print_copies_default', 'order_disposables',
            ]),
            'cover_url' => MediaUrl::fromPath($branch->cover_image_path),
            'public_url' => url("/{$tenant->slug}/{$branch->slug}"),
            'rating_summary' => $ratingSummary ?? $this->ratings->branchAveragesForMany($tenant->id, [$branch->id])[$branch->id] ?? [
                'restaurant' => null,
                'order' => null,
                'delivery' => null,
                'count' => 0,
            ],
        ];
    }

    protected function uniqueSlug(Tenant $tenant, string $slug): string
    {
        $base = $slug;
        $i = 1;

        while (Branch::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    protected function ensureBranchBelongsToTenant(Tenant $tenant, Branch $branch): void
    {
        if ($branch->tenant_id !== $tenant->id) {
            abort(404);
        }
    }
}
