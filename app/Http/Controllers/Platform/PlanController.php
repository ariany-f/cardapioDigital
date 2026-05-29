<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->planRules());

        $slug = $data['slug'] ?? Str::slug($data['name']);

        Plan::create([
            'name' => $data['name'],
            'slug' => $slug,
            'price_monthly' => $data['price_monthly'],
            'is_active' => $request->boolean('is_active', true),
            'features_json' => $this->normalizeFeatures($data['features_json']),
        ]);

        return back()->with('success', 'Plano criado.');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $request->validate($this->planRules($plan));

        $plan->update([
            'name' => $data['name'],
            'price_monthly' => $data['price_monthly'],
            'is_active' => $request->boolean('is_active'),
            'features_json' => $this->normalizeFeatures($data['features_json']),
        ]);

        return back()->with('success', 'Plano atualizado.');
    }

    /** @return array<string, mixed> */
    protected function planRules(?Plan $plan = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'features_json' => ['required', 'array'],
            'features_json.max_branches' => ['required', 'integer', 'min:1', 'max:999'],
            'features_json.delivery_webhooks' => ['boolean'],
            'features_json.kds' => ['boolean'],
            'features_json.reports' => ['boolean'],
            'features_json.pos' => ['boolean'],
            'features_json.motoboys' => ['boolean'],
        ];

        if ($plan === null) {
            $rules['slug'] = ['nullable', 'string', 'max:64', 'alpha_dash', Rule::unique('plans', 'slug')];
        }

        return $rules;
    }

    /** @param  array<string, mixed>  $features */
    protected function normalizeFeatures(array $features): array
    {
        return [
            'max_branches' => max(1, (int) ($features['max_branches'] ?? 1)),
            'delivery_webhooks' => filter_var($features['delivery_webhooks'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'kds' => filter_var($features['kds'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'reports' => filter_var($features['reports'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'pos' => filter_var($features['pos'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'motoboys' => filter_var($features['motoboys'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
