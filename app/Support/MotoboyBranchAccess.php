<?php

namespace App\Support;

use App\Models\Motoboy;
use Illuminate\Database\Eloquent\Builder;

class MotoboyBranchAccess
{
    public static function hasUnrestrictedAccess(?Motoboy $motoboy): bool
    {
        if (! $motoboy) {
            return false;
        }

        if ($motoboy->access_all_branches === true) {
            return true;
        }

        if ($motoboy->access_all_branches === null) {
            return true;
        }

        return false;
    }

    /**
     * @return list<int>|null null = todas as filiais do restaurante
     */
    public static function allowedBranchIds(?Motoboy $motoboy): ?array
    {
        if (! $motoboy || self::hasUnrestrictedAccess($motoboy)) {
            return null;
        }

        return $motoboy->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all();
    }

    public static function canServeBranch(?Motoboy $motoboy, int $branchId): bool
    {
        $allowed = self::allowedBranchIds($motoboy);

        if ($allowed === null) {
            return true;
        }

        return in_array($branchId, $allowed, true);
    }

    public static function assertCanServeBranch(?Motoboy $motoboy, int $branchId): void
    {
        if (! self::canServeBranch($motoboy, $branchId)) {
            abort(422, 'Este entregador não atende a filial deste pedido.');
        }
    }

    public static function scopeForBranch(Builder $query, int $branchId): Builder
    {
        return $query->where(function (Builder $q) use ($branchId) {
            $q->where('access_all_branches', true)
                ->orWhereNull('access_all_branches')
                ->orWhereHas('branches', fn (Builder $b) => $b->where('branches.id', $branchId));
        });
    }

    public static function scopeDeliveryBranch(Builder $query, string $orderBranchColumn = 'orders.branch_id'): Builder
    {
        return $query->where(function (Builder $q) use ($orderBranchColumn) {
            $q->where('access_all_branches', true)
                ->orWhereNull('access_all_branches')
                ->orWhereHas('branches', function (Builder $b) use ($orderBranchColumn) {
                    $b->whereColumn('branches.id', $orderBranchColumn);
                });
        });
    }
}
