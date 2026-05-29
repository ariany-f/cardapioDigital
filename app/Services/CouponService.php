<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Coupon;
use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function apply(?string $code, float $subtotal, Branch $branch): array
    {
        if (! $code || trim($code) === '') {
            return ['coupon' => null, 'discount' => 0.0];
        }

        $coupon = Coupon::query()
            ->where('code', strtoupper(trim($code)))
            ->where('is_active', true)
            ->where(function ($q) use ($branch) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branch->id);
            })
            ->first();

        if (! $coupon) {
            throw ValidationException::withMessages([
                'coupon_code' => ['Cupom inválido ou expirado.'],
            ]);
        }

        if ($coupon->valid_from && now()->lt($coupon->valid_from)) {
            throw ValidationException::withMessages(['coupon_code' => ['Cupom ainda não está válido.']]);
        }

        if ($coupon->valid_until && now()->gt($coupon->valid_until)) {
            throw ValidationException::withMessages(['coupon_code' => ['Cupom expirado.']]);
        }

        if ($coupon->max_uses && $coupon->uses_count >= $coupon->max_uses) {
            throw ValidationException::withMessages(['coupon_code' => ['Cupom esgotado.']]);
        }

        if ($coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
            throw ValidationException::withMessages([
                'coupon_code' => [
                    sprintf('Pedido mínimo de R$ %.2f para este cupom.', $coupon->min_order_amount),
                ],
            ]);
        }

        $discount = $coupon->type === 'percent'
            ? round($subtotal * ((float) $coupon->value / 100), 2)
            : min((float) $coupon->value, $subtotal);

        return ['coupon' => $coupon, 'discount' => $discount];
    }

    public function incrementUsage(Coupon $coupon): void
    {
        $coupon->increment('uses_count');
    }
}
