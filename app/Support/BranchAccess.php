<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BranchAccess
{
    public static function hasUnrestrictedBranchAccess(?User $user): bool
    {
        if (! $user || $user->is_platform_user) {
            return true;
        }

        if ($user->hasRole('tenant_admin')) {
            return true;
        }

        // null = legado (antes da coluna); só restringe quando explicitamente false
        return $user->access_all_branches !== false;
    }

    /**
     * @return list<int>|null null = todas as filiais do tenant
     */
    public static function allowedBranchIds(?User $user): ?array
    {
        if (self::hasUnrestrictedBranchAccess($user)) {
            return null;
        }

        return $user->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all();
    }

    public static function canAccessBranch(?User $user, int $branchId): bool
    {
        $allowed = self::allowedBranchIds($user);

        if ($allowed === null) {
            return true;
        }

        return in_array($branchId, $allowed, true);
    }

    public static function scopeBranchColumn(Builder $query, string $column, ?User $user): Builder
    {
        $allowed = self::allowedBranchIds($user);

        if ($allowed === null) {
            return $query;
        }

        if ($allowed === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $allowed);
    }

    public static function branchesForUser(?User $user)
    {
        $query = Branch::query()->orderBy('name');

        return self::scopeBranchColumn($query, 'branches.id', $user)->get();
    }

    public static function assertCanAccessBranch(?User $user, int $branchId): void
    {
        if (! self::canAccessBranch($user, $branchId)) {
            abort(403, 'Você não tem acesso a esta filial.');
        }
    }
}
