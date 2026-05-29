<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesBranchCoverUpload;
use App\Http\Controllers\Concerns\ValidatesBranch;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Support\MediaUrl;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    use HandlesBranchCoverUpload;
    use ValidatesBranch;

    public function index(): Response
    {
        return $this->branchesPage();
    }

    public function create(): Response
    {
        return $this->branchesPage(['creating' => true]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::get();

        $data = $request->validate([
            ...$this->branchRules($tenant->id),
            ...$this->coverImageRules(),
        ]);

        $slug = $data['slug'] ?? Str::slug($data['name']);
        $slug = $this->uniqueSlug($tenant->id, $slug);

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            ...$this->branchAttributes([
                ...$data,
                'opening_hours' => $this->normalizeOpeningHours($data['opening_hours'] ?? null),
            ], $slug),
        ]);

        $this->storeBranchCover($request, $branch, $tenant->id);

        return redirect()->route('tenant.admin.branches.index', ['tenant' => $tenant->slug])
            ->with('success', 'Filial criada.');
    }

    public function edit(string $tenant, Branch $branch): Response
    {
        return $this->branchesPage([
            'editingBranch' => $this->branchPayload($branch),
        ]);
    }

    public function update(Request $request, string $tenant, Branch $branch): RedirectResponse
    {
        $tenantModel = TenantContext::get();

        $data = $request->validate([
            ...$this->branchRules($tenantModel->id, $branch),
            ...$this->coverImageRules(),
        ]);

        $slug = $data['slug'] ?? $branch->slug;

        $branch->update($this->branchAttributes([
            ...$data,
            'opening_hours' => $this->normalizeOpeningHours($data['opening_hours'] ?? null),
        ], $slug));

        $this->storeBranchCover($request, $branch, $tenantModel->id);

        return redirect()->route('tenant.admin.branches.edit', ['tenant' => $tenant, 'branch' => $branch])
            ->with('success', 'Filial atualizada.');
    }

    public function destroy(string $tenant, Branch $branch): RedirectResponse
    {
        $branch->delete();

        return redirect()->route('tenant.admin.branches.index', ['tenant' => $tenant])
            ->with('success', 'Filial removida.');
    }

    protected function branchesPage(array $extra = []): Response
    {
        $tenantSlug = request()->route('tenant');

        return Inertia::render('Admin/Branches/Index', [
            'branches' => Branch::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Branch $branch) => $this->branchPayload($branch, $tenantSlug)),
            ...$extra,
        ]);
    }

    protected function branchPayload(Branch $branch, ?string $tenantSlug = null): array
    {
        $tenantSlug ??= request()->route('tenant');

        return [
            ...$branch->only([
                'id', 'name', 'slug', 'phone', 'instagram', 'is_active',
                'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'postal_code',
                'latitude', 'longitude', 'opening_hours', 'public_description',
                'pickup_available', 'delivery_available', 'delivery_radius_km',
                'minimum_order_amount', 'packaging_fee_default', 'default_prep_time_minutes', 'delivery_time_minutes',
                'auto_accept_orders', 'allow_scheduled_orders', 'auto_print_on_new_order',
                'notification_email', 'print_format', 'print_copies_default', 'order_disposables',
            ]),
            'cover_url' => MediaUrl::fromPath($branch->cover_image_path),
            'public_url' => url("/{$tenantSlug}/{$branch->slug}"),
        ];
    }

    protected function uniqueSlug(int $tenantId, string $slug): string
    {
        $base = $slug;
        $i = 1;

        while (Branch::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
