<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\LogsCrudActivity;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => Coupon::query()->with('branch:id,name')->latest()->get(),
            'branches' => Branch::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $coupon = Coupon::create([
            'code' => strtoupper(trim($data['code'])),
            'type' => $data['type'],
            'value' => $data['value'],
            'branch_id' => $data['branch_id'] ?? null,
            'min_order_amount' => $data['min_order_amount'] ?? null,
            'max_uses' => $data['max_uses'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
        $this->logCrud($coupon, 'coupon.created', 'Cupom criado', ['code' => $coupon->code]);

        return back()->with('success', 'Cupom criado.');
    }

    public function update(Request $request, string $tenant, Coupon $coupon): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $coupon->update([
            ...$data,
            'code' => strtoupper(trim($data['code'])),
        ]);
        $this->logCrud($coupon, 'coupon.updated', 'Cupom atualizado', ['code' => $coupon->code]);

        return back()->with('success', 'Cupom atualizado.');
    }

    public function destroy(string $tenant, Coupon $coupon): RedirectResponse
    {
        $this->logCrud($coupon, 'coupon.deleted', 'Cupom removido', ['code' => $coupon->code]);
        $coupon->delete();

        return back()->with('success', 'Cupom removido.');
    }
}
