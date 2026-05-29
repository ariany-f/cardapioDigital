<?php

namespace App\Support;

use App\Models\Plan;

class MarketingPlans
{
    /**
     * @return array{plans: list<array<string, mixed>>, featured: ?array<string, mixed>}
     */
    public static function forLanding(): array
    {
        $models = Plan::query()
            ->where('is_active', true)
            ->orderBy('price_monthly')
            ->orderBy('name')
            ->get();

        if ($models->isEmpty()) {
            $fallback = self::fallbackPlan();

            return [
                'plans' => [$fallback],
                'featured' => $fallback,
            ];
        }

        $featuredId = $models->first()->id;
        $plans = $models->map(fn (Plan $plan) => self::formatPlan($plan, $plan->id === $featuredId))->values()->all();

        return [
            'plans' => $plans,
            'featured' => $plans[0],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatPlan(Plan $plan, bool $isFeatured = false): array
    {
        $price = (float) $plan->price_monthly;

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'price' => $price,
            'price_formatted' => number_format($price, 2, ',', '.'),
            'features_json' => $plan->features_json ?? [],
            'is_featured' => $isFeatured,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function fallbackPlan(): array
    {
        $slug = config('marketing.plan_slug', 'basico');
        $price = (float) config('marketing.plan_price_monthly', 29.90);

        return [
            'id' => null,
            'name' => config('marketing.plan_name', 'Básico'),
            'slug' => $slug,
            'price' => $price,
            'price_formatted' => number_format($price, 2, ',', '.'),
            'features_json' => [],
            'is_featured' => true,
        ];
    }

    public static function featuredPlanModel(): ?Plan
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('price_monthly')
            ->orderBy('name')
            ->first();
    }
}
