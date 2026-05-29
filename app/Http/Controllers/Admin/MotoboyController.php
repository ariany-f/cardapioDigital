<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Motoboy;
use App\Services\OrderRatingService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MotoboyController extends Controller
{
    public function index(OrderRatingService $ratings): Response
    {
        $tenantId = TenantContext::id();
        $ratingMap = $tenantId ? $ratings->motoboyRatingMap($tenantId) : [];

        $motoboys = Motoboy::query()
            ->with('branches:id,name')
            ->withCount([
                'deliveries as active_deliveries_count' => fn ($q) => $q->whereIn('status', Motoboy::ACTIVE_DELIVERY_STATUSES),
                'deliveries as total_deliveries_count',
                'reports as open_reports_count' => fn ($q) => $q->where('status', 'open'),
                'reports as total_reports_count',
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Motoboy $m) use ($ratingMap) {
                $rating = $ratingMap[$m->id] ?? null;

                return [
                    ...$m->toArray(),
                    'branch_ids' => $m->branches->pluck('id')->values()->all(),
                    'branch_names' => $m->branches->pluck('name')->values()->all(),
                    'login_status' => $this->loginStatus($m),
                    'delivery_rating_average' => $rating['average'] ?? null,
                    'delivery_rating_count' => $rating['count'] ?? 0,
                    'open_reports_count' => (int) ($m->open_reports_count ?? 0),
                    'total_reports_count' => (int) ($m->total_reports_count ?? 0),
                ];
            });

        $withApp = $motoboys->where('uses_app', true);

        return Inertia::render('Admin/Motoboys/Index', [
            'motoboys' => $motoboys,
            'entregadorLoginUrl' => route('tenant.entregador.login', ['tenant' => TenantContext::get()->slug]),
            'loginStats' => [
                'total' => $motoboys->count(),
                'with_app' => $withApp->count(),
                'with_login' => $withApp->where('has_app_login', true)->count(),
                'pending_login' => $withApp->where('has_app_login', false)->count(),
                'printed_only' => $motoboys->where('uses_app', false)->count(),
            ],
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'formOptions' => [
                'vehicle_types' => $this->labeledOptions(Motoboy::VEHICLE_TYPES, [
                    'motorcycle' => 'Moto',
                    'bicycle' => 'Bicicleta',
                    'car' => 'Carro',
                    'van' => 'Van / utilitário',
                    'on_foot' => 'A pé',
                ]),
                'employment_types' => $this->labeledOptions(Motoboy::EMPLOYMENT_TYPES, [
                    'clt' => 'CLT (funcionário)',
                    'pj' => 'PJ',
                    'freelancer' => 'Autônomo / freelancer',
                    'partner' => 'Parceiro / terceirizado',
                ]),
                'pix_key_types' => $this->labeledOptions(Motoboy::PIX_KEY_TYPES, [
                    'cpf' => 'CPF',
                    'cnpj' => 'CNPJ',
                    'email' => 'E-mail',
                    'phone' => 'Telefone',
                    'random' => 'Chave aleatória',
                ]),
                'operational_statuses' => $this->labeledOptions(Motoboy::OPERATIONAL_STATUSES, [
                    'available' => 'Disponível',
                    'busy' => 'Em entrega',
                    'offline' => 'Offline',
                    'on_break' => 'Em pausa',
                ]),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $branchIds = $data['branch_ids'];
        unset($data['branch_ids']);

        $motoboy = Motoboy::create($data);
        $this->syncBranches($motoboy, $branchIds, (bool) $data['access_all_branches']);

        return back()->with('success', 'Entregador cadastrado.');
    }

    public function update(Request $request, string $tenant, Motoboy $motoboy): RedirectResponse
    {
        $data = $this->validated($request, $motoboy);
        $branchIds = $data['branch_ids'];
        unset($data['branch_ids']);

        $motoboy->update($data);
        $this->syncBranches($motoboy, $branchIds, (bool) $data['access_all_branches']);

        return back()->with('success', 'Entregador atualizado.');
    }

    public function updateLogin(Request $request, string $tenant, Motoboy $motoboy): RedirectResponse
    {
        $tenantId = $motoboy->tenant_id;

        $data = $request->validate([
            'uses_app' => ['required', 'boolean'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('motoboys', 'email')->where('tenant_id', $tenantId)->ignore($motoboy->id),
            ],
            'password' => ['nullable', Password::defaults()],
            'is_active' => ['boolean'],
        ]);

        $usesApp = $request->boolean('uses_app');

        if ($usesApp && empty($data['email'])) {
            throw ValidationException::withMessages([
                'email' => 'Informe o e-mail de login do painel web.',
            ]);
        }

        if ($usesApp && ! filled($motoboy->getRawOriginal('password')) && empty($data['password'])) {
            throw ValidationException::withMessages([
                'password' => 'Defina uma senha para liberar o acesso ao painel.',
            ]);
        }

        $update = [
            'uses_app' => $usesApp,
            'is_active' => $request->boolean('is_active', true),
            'email' => $usesApp ? $data['email'] : null,
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        } elseif (! $usesApp) {
            $update['password'] = null;
        }

        $motoboy->update($update);

        return back()->with('success', $usesApp ? 'Acesso ao painel web atualizado.' : 'Entregador definido como somente impresso.');
    }

    public function resetPassword(Request $request, string $tenant, Motoboy $motoboy): RedirectResponse
    {
        if (! $motoboy->uses_app) {
            return back()->with('error', 'Este entregador não utiliza o aplicativo.');
        }

        $data = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $motoboy->update(['password' => $data['password']]);

        return back()->with('success', 'Senha do entregador redefinida.');
    }

    public function destroy(string $tenant, Motoboy $motoboy): RedirectResponse
    {
        if ($motoboy->deliveries()->whereIn('status', Motoboy::ACTIVE_DELIVERY_STATUSES)->exists()) {
            return back()->with('error', 'Não é possível remover: há entregas em andamento.');
        }

        $motoboy->delete();

        return back()->with('success', 'Entregador removido.');
    }

    protected function validated(Request $request, ?Motoboy $motoboy = null): array
    {
        $tenantId = $motoboy?->tenant_id ?? TenantContext::id() ?? $request->user()?->tenant_id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('motoboys', 'email')->where('tenant_id', $tenantId)->ignore($motoboy?->id),
            ],
            'password' => [$motoboy ? 'nullable' : 'nullable', 'string', 'min:6'],
            'document_rg' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'postal_code' => ['nullable', 'string', 'max:12'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'vehicle_type' => ['required', Rule::in(Motoboy::VEHICLE_TYPES)],
            'vehicle' => ['nullable', 'string', 'max:100'],
            'license_plate' => ['nullable', 'string', 'max:15'],
            'cnh_number' => ['nullable', 'string', 'max:20'],
            'cnh_category' => ['nullable', 'string', 'max:10'],
            'cnh_expires_at' => ['nullable', 'date'],
            'pix_key_type' => ['nullable', Rule::in(Motoboy::PIX_KEY_TYPES)],
            'pix_key' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['required', Rule::in(Motoboy::EMPLOYMENT_TYPES)],
            'employee_code' => ['nullable', 'string', 'max:30'],
            'hired_at' => ['nullable', 'date'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'operational_status' => ['required', Rule::in(Motoboy::OPERATIONAL_STATUSES)],
            'max_active_deliveries' => ['required', 'integer', 'min:1', 'max:20'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'uses_app' => ['boolean'],
            'access_all_branches' => ['boolean'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => [
                'integer',
                Rule::exists('branches', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        $data['uses_app'] = $request->boolean('uses_app', true);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['access_all_branches'] = $request->boolean('access_all_branches', true);
        $data['branch_ids'] = $data['branch_ids'] ?? [];

        if (! $data['uses_app']) {
            $data['email'] = null;
            $data['password'] = null;
        } elseif (! $motoboy && empty($data['email'])) {
            throw ValidationException::withMessages([
                'email' => 'Informe o e-mail para entregadores que usam o painel web.',
            ]);
        } elseif ($data['uses_app'] && ! $motoboy && empty($data['password'])) {
            throw ValidationException::withMessages([
                'password' => 'Defina uma senha para o acesso ao painel web.',
            ]);
        }

        if (! $data['access_all_branches'] && $data['branch_ids'] === []) {
            throw ValidationException::withMessages([
                'branch_ids' => 'Selecione pelo menos uma filial que este entregador atende.',
            ]);
        }

        foreach (['pix_key_type', 'commission_percent', 'cpf', 'email', 'birth_date', 'hired_at', 'cnh_expires_at'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }

    protected function syncBranches(Motoboy $motoboy, array $branchIds, bool $accessAll): void
    {
        if ($accessAll) {
            $motoboy->branches()->sync([]);
            $motoboy->update(['access_all_branches' => true]);

            return;
        }

        $motoboy->update(['access_all_branches' => false]);
        $motoboy->branches()->sync($branchIds);
    }

    protected function labeledOptions(array $values, array $labels): array
    {
        return collect($values)
            ->map(fn (string $value) => ['value' => $value, 'label' => $labels[$value] ?? $value])
            ->values()
            ->all();
    }

    protected function loginStatus(Motoboy $motoboy): string
    {
        if (! $motoboy->is_active) {
            return 'inactive';
        }

        if (! $motoboy->uses_app) {
            return 'printed_only';
        }

        if (! filled($motoboy->email)) {
            return 'missing_email';
        }

        if (! filled($motoboy->getRawOriginal('password'))) {
            return 'missing_password';
        }

        return 'ready';
    }
}
