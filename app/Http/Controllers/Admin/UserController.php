<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\LogsCrudActivity;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Support\RolePermissionsCatalog;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use LogsCrudActivity;
    public function index(): Response
    {
        $tenant = TenantContext::get();

        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()
                ->where('is_platform_user', false)
                ->with('branches:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->getRoleNames(),
                    'is_protected_admin' => $user->is_protected_admin,
                    'access_all_branches' => (bool) $user->access_all_branches,
                    'branch_ids' => $user->branches->pluck('id')->values()->all(),
                    'branch_names' => $user->branches->pluck('name')->values()->all(),
                ]),
            'roles' => Role::query()
                ->whereIn('name', ['tenant_admin', 'manager', 'operator', 'viewer', 'branch_staff'])
                ->orderBy('name')
                ->pluck('name'),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
            'rolePermissions' => RolePermissionsCatalog::allForTenantRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request);

        $tenant = TenantContext::get();

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'is_platform_user' => false,
            'is_protected_admin' => false,
            'access_all_branches' => $data['access_all_branches'],
        ]);

        $user->syncRoles([$data['role']]);
        $this->syncBranches($user, $data);
        $this->logCrud($user, 'user.created', 'Usuário criado', ['email' => $user->email]);

        return back()->with('success', 'Usuário criado.');
    }

    public function update(Request $request, string $tenant, User $user): RedirectResponse
    {
        $tenantModel = TenantContext::get();

        if ($user->is_platform_user || $user->tenant_id !== $tenantModel->id) {
            abort(403);
        }

        $data = $this->validateUser($request, $user);

        if ($user->is_protected_admin && $data['role'] !== 'tenant_admin') {
            return back()->withErrors(['role' => 'Este usuário protegido deve permanecer como administrador.']);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'access_all_branches' => $user->is_protected_admin ? true : $data['access_all_branches'],
            ...($data['password'] ? ['password' => Hash::make($data['password'])] : []),
        ]);

        if (! $user->is_protected_admin) {
            $user->syncRoles([$data['role']]);
            $this->syncBranches($user, $data);
        }
        $this->logCrud($user, 'user.updated', 'Usuário atualizado', ['email' => $user->email]);

        return back()->with('success', 'Usuário atualizado.');
    }

    public function destroy(string $tenant, User $user): RedirectResponse
    {
        $tenantModel = TenantContext::get();

        if ($user->is_platform_user || $user->tenant_id !== $tenantModel->id || $user->is_protected_admin) {
            abort(403, 'Este usuário não pode ser removido.');
        }

        if ($user->id === auth()->id()) {
            abort(403, 'Você não pode remover sua própria conta.');
        }

        $this->logCrud($user, 'user.deleted', 'Usuário removido', ['email' => $user->email]);
        $user->delete();

        return back()->with('success', 'Usuário removido.');
    }

    protected function validateUser(Request $request, ?User $user = null): array
    {
        $tenant = TenantContext::get();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$user ? 'nullable' : 'required', Password::defaults()],
            'role' => ['required', Rule::in(['tenant_admin', 'manager', 'operator', 'viewer', 'branch_staff'])],
            'access_all_branches' => ['boolean'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => [
                'integer',
                Rule::exists('branches', 'id')->where('tenant_id', $tenant->id),
            ],
        ]);

        $data['access_all_branches'] = $request->boolean('access_all_branches', true);

        if ($data['role'] === 'branch_staff') {
            $data['access_all_branches'] = false;
        }

        if ($data['role'] === 'tenant_admin') {
            $data['access_all_branches'] = true;
        }

        if (! $data['access_all_branches'] && empty($data['branch_ids'])) {
            throw ValidationException::withMessages([
                'branch_ids' => 'Selecione pelo menos uma filial para este usuário.',
            ]);
        }

        return $data;
    }

    protected function syncBranches(User $user, array $data): void
    {
        if ($data['role'] === 'tenant_admin' || ($data['access_all_branches'] ?? true)) {
            $user->branches()->sync([]);
            $user->update(['access_all_branches' => true]);

            return;
        }

        $branchIds = $data['branch_ids'] ?? [];
        $user->update(['access_all_branches' => false]);
        $user->branches()->sync($branchIds);
    }
}
