<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Platform/Plans/Index', [
            'plans' => Plan::orderBy('price_monthly')->get(),
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'features_json' => ['nullable', 'array'],
        ]);

        $plan->update([
            'name' => $data['name'],
            'price_monthly' => $data['price_monthly'],
            'is_active' => $request->boolean('is_active'),
            'features_json' => $data['features_json'] ?? $plan->features_json,
        ]);

        return back()->with('success', 'Plano atualizado.');
    }
}
