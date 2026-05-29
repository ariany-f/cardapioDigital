<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Platform\Concerns\ValidatesPlatformTenant;
use App\Models\Language;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Validation\ValidationException;
use App\Models\TenantSubscription;
use App\Services\OrderRatingService;
use App\Support\TenantFeatures;
use App\Support\TenantPlanFeatures;
use App\Support\TenantRegionalOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    use ValidatesPlatformTenant;

    public function __construct(
        protected OrderRatingService $ratings,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Tenants/Index', $this->tenantIndexProps());
    }

    public function create(): Response
    {
        return Inertia::render('Platform/Tenants/Index', $this->tenantIndexProps([
            'creating' => true,
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(
            $this->tenantRules(creating: true),
            [],
            $this->tenantValidationAttributes(),
        );

        $plan = Plan::query()->findOrFail($data['plan_id']);
        $this->validatePlanModuleFlags($plan, $data);

        $slug = $data['slug'] ?? Str::slug($data['name']);
        $baseSlug = $slug;
        $i = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$i++;
        }

        $tenant = Tenant::create([
            ...$this->tenantAttributes($data),
            'slug' => $slug,
            'status' => 'active',
            'document_type' => $data['document_type'] ?? 'cnpj',
            'default_locale' => $data['default_locale'] ?? 'pt_BR',
            'currency' => $data['currency'] ?? 'BRL',
            'timezone' => $data['timezone'] ?? 'America/Sao_Paulo',
        ]);

        $this->applyTenantFeatureFlags($tenant, $data, (int) $data['plan_id']);

        TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $data['plan_id'],
            'current_period_start' => now()->startOfMonth(),
            'current_period_end' => now()->endOfMonth(),
            'payment_status' => 'pending',
            'status' => 'active',
        ]);

        return redirect()->route('platform.tenants.index')
            ->with('success', 'Restaurante criado com sucesso.');
    }

    public function show(Tenant $tenant): Response
    {
        $tenant->load(['branches', 'activeSubscription.plan', 'subscriptions.plan']);
        $recentPayments = $tenant->payments()
            ->with('markedBy:id,name')
            ->latest('paid_at')
            ->limit(5)
            ->get();

        return Inertia::render('Platform/Tenants/Index', [
            ...$this->tenantIndexProps(),
            'selectedTenant' => $this->tenantWithRatingSummary($tenant),
            'recentPayments' => $recentPayments,
        ]);
    }

    public function edit(Tenant $tenant): Response
    {
        $tenant->load('activeSubscription');

        return Inertia::render('Platform/Tenants/Index', $this->tenantIndexProps([
            'editingTenant' => $this->tenantForForm($tenant),
        ]));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate(
            $this->tenantRules($tenant),
            [],
            $this->tenantValidationAttributes(),
        );

        $tenant->update([
            ...$this->tenantAttributes($data),
            'slug' => $data['slug'] ?? $tenant->slug,
            'document_type' => $data['document_type'] ?? $tenant->document_type,
        ]);

        $this->applyTenantFeatureFlags($tenant, $data);

        return redirect()->route('platform.tenants.edit', $tenant)
            ->with('success', 'Restaurante atualizado.');
    }

    public function suspend(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'suspension_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $tenant->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $data['suspension_reason'] ?? null,
        ]);

        return back()->with('success', 'Restaurante suspenso.');
    }

    public function activate(Tenant $tenant): RedirectResponse
    {
        $tenant->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        return back()->with('success', 'Restaurante reativado.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $name = $tenant->name;

        $tenant->delete();

        return redirect()->route('platform.tenants.index')
            ->with('success', "Restaurante \"{$name}\" excluído permanentemente.");
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function applyTenantFeatureFlags(Tenant $tenant, array $data, ?int $planId = null): void
    {
        $plan = $planId
            ? Plan::query()->find($planId)
            : $tenant->activeSubscription()->with('plan')->first()?->plan;

        $this->applyTenantFeatureFlag($tenant, $data, $plan, 'motoboys', 'motoboys_enabled', 'platform.delivery.motoboys_plan_blocked', fn (Tenant $t, bool $enabled) => TenantFeatures::setMotoboysEnabled($t, $enabled));
        $this->applyTenantFeatureFlag($tenant, $data, $plan, 'pos', 'pos_enabled', 'platform.plans.pos_plan_blocked', fn (Tenant $t, bool $enabled) => TenantFeatures::setPosEnabled($t, $enabled));
        $this->applyTenantFeatureFlag($tenant, $data, $plan, 'kds', 'kds_enabled', 'platform.plans.kds_plan_blocked', fn (Tenant $t, bool $enabled) => TenantFeatures::setKdsEnabled($t, $enabled));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validatePlanModuleFlags(Plan $plan, array $data): void
    {
        foreach ([
            ['motoboys', 'motoboys_enabled', 'platform.delivery.motoboys_plan_blocked'],
            ['pos', 'pos_enabled', 'platform.plans.pos_plan_blocked'],
            ['kds', 'kds_enabled', 'platform.plans.kds_plan_blocked'],
        ] as [$planFeature, $inputKey, $messageKey]) {
            if (! array_key_exists($inputKey, $data)) {
                continue;
            }

            $enabled = filter_var($data[$inputKey], FILTER_VALIDATE_BOOLEAN);

            if ($enabled && ! TenantPlanFeatures::planAllows($plan, $planFeature)) {
                throw ValidationException::withMessages([
                    $inputKey => [__($messageKey)],
                ]);
            }
        }
    }

    /**
     * @param  callable(Tenant, bool): void  $setter
     */
    protected function applyTenantFeatureFlag(
        Tenant $tenant,
        array $data,
        ?Plan $plan,
        string $planFeature,
        string $inputKey,
        string $blockedMessageKey,
        callable $setter,
    ): void {
        if (! array_key_exists($inputKey, $data)) {
            return;
        }

        $enabled = filter_var($data[$inputKey], FILTER_VALIDATE_BOOLEAN);

        if ($enabled && ! TenantPlanFeatures::planAllows($plan, $planFeature)) {
            throw ValidationException::withMessages([
                $inputKey => [__($blockedMessageKey)],
            ]);
        }

        $setter($tenant, $enabled);
    }

    /**
     * @return array<string, mixed>
     */
    protected function tenantForForm(Tenant $tenant): array
    {
        $tenant->loadMissing('activeSubscription.plan');
        $data = $tenant->toArray();
        $data['motoboys_disable_blocked'] = TenantFeatures::hasMotoboyDeliveriesInProgress($tenant);
        $data['motoboy_deliveries_in_progress_count'] = TenantFeatures::motoboyDeliveriesInProgressCount($tenant);
        $data['plan_motoboys_included'] = TenantFeatures::motoboysAllowedByPlan($tenant);
        $data['plan_pos_included'] = TenantFeatures::posAllowedByPlan($tenant);
        $data['plan_kds_included'] = TenantFeatures::kdsAllowedByPlan($tenant);

        return $data;
    }

    protected function tenantIndexProps(array $extra = []): array
    {
        return array_merge([
            'tenants' => $this->tenantsPaginator(),
            'plans' => Plan::where('is_active', true)->get(['id', 'name', 'slug', 'price_monthly', 'features_json']),
            'languages' => Language::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['code', 'name', 'flag', 'is_default']),
            'currencies' => TenantRegionalOptions::currencies(),
            'timezones' => TenantRegionalOptions::timezones(),
        ], $extra);
    }

    protected function tenantsPaginator(): LengthAwarePaginator
    {
        $paginator = Tenant::query()
            ->with(['activeSubscription.plan'])
            ->latest()
            ->paginate(15);

        $ids = $paginator->getCollection()->pluck('id')->all();
        $averages = $this->ratings->tenantAveragesForMany($ids);

        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn (Tenant $tenant) => $this->tenantWithRatingSummary(
                    $tenant,
                    $averages[$tenant->id] ?? null,
                ),
            ),
        );

        return $paginator;
    }

    /**
     * @param  array{restaurant: ?float, order: ?float, delivery: ?float, count: int}|null  $summary
     * @return array<string, mixed>
     */
    protected function tenantWithRatingSummary(Tenant $tenant, ?array $summary = null): array
    {
        return array_merge($tenant->toArray(), [
            'rating_summary' => $summary ?? $this->ratings->tenantAverages($tenant->id),
        ]);
    }
}
