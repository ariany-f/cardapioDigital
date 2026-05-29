<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Combo;
use App\Models\Product;
use App\Models\ProductVariationGroup;
use App\Models\Tenant;
use App\Support\InstagramLink;
use App\Support\MediaUrl;
use App\Support\OrderDisposableConfig;
use App\Support\TenantContext;
use App\Support\TenantOrderSettings;
use Illuminate\Support\Collection;

class BranchCatalogService
{
    public function categoriesFor(Branch $branch): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->where('is_paused', false)
            ->orderBy('sort_order')
            ->with(['products' => function ($query) use ($branch) {
                $query->where('is_active', true)
                    ->where('is_paused', false)
                    ->whereHas('branches', fn ($q) => $q->where('branches.id', $branch->id))
                    ->with(['variationGroups' => fn ($q) => $q->orderBy('sort_order')->with(['options' => fn ($o) => $o->orderBy('sort_order')])])
                    ->orderByDesc('is_featured')
                    ->orderBy('name');
            }])
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'products' => $cat->products
                    ->filter(fn ($p) => $p->isAvailable())
                    ->map(fn ($p) => $this->productPayload($p))
                    ->values(),
            ])
            ->filter(fn ($cat) => $cat['products']->isNotEmpty())
            ->values();
    }

    public function featuredFor(Branch $branch): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->where('is_paused', false)
            ->where('is_featured', true)
            ->whereHas('branches', fn ($q) => $q->where('branches.id', $branch->id))
            ->with(['variationGroups' => fn ($q) => $q->orderBy('sort_order')->with(['options' => fn ($o) => $o->orderBy('sort_order')])])
            ->orderBy('name')
            ->get()
            ->filter(fn ($p) => $p->isAvailable())
            ->map(fn ($p) => $this->productPayload($p));
    }

    public function combosFor(Branch $branch): Collection
    {
        return Combo::query()
            ->where('is_active', true)
            ->where(function ($q) use ($branch) {
                $q->where('branch_id', $branch->id)->orWhereNull('branch_id');
            })
            ->with(['items.product:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($combo) => [
                'id' => $combo->id,
                'name' => $combo->name,
                'description' => $combo->description,
                'price' => $combo->price,
                'image_url' => MediaUrl::fromPath($combo->image_path),
                'items' => $combo->items->map(fn ($item) => [
                    'product_name' => $item->product?->name,
                    'quantity' => $item->quantity,
                ]),
            ]);
    }

    public function bannersFor(Branch $branch): Collection
    {
        return Banner::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'image_url' => MediaUrl::fromPath($b->image_path),
                'link_url' => $b->link_url,
            ]);
    }

    public function combosForPos(): Collection
    {
        return Combo::query()
            ->where('is_active', true)
            ->with(['items.product:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($combo) => [
                'id' => $combo->id,
                'name' => $combo->name,
                'description' => $combo->description,
                'price' => $combo->price,
                'branch_id' => $combo->branch_id,
                'image_url' => MediaUrl::fromPath($combo->image_path),
                'items' => $combo->items->map(fn ($item) => [
                    'product_name' => $item->product?->name,
                    'quantity' => $item->quantity,
                ]),
            ]);
    }

    public function productsForPos(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->where('is_paused', false)
            ->with([
                'branches:id',
                'variationGroups' => fn ($q) => $q->orderBy('sort_order')->with(['options' => fn ($o) => $o->orderBy('sort_order')]),
            ])
            ->orderBy('name')
            ->get()
            ->filter(fn ($p) => $p->isAvailable())
            ->map(fn ($p) => [
                ...$this->productPayload($p),
                'branch_ids' => $p->branches->pluck('id')->values()->all(),
            ]);
    }

    public function productPayload(Product $product): array
    {
        $groups = $product->variationGroups->map(fn ($g) => $this->groupPayload($g));

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'base_price' => $product->base_price,
            'is_featured' => $product->is_featured,
            'tags' => $product->tags ?? [],
            'prep_time_minutes' => $product->prep_time_minutes,
            'image_url' => MediaUrl::fromPath($product->image_path),
            'out_of_stock' => $product->track_stock && ($product->stock_quantity ?? 0) <= 0,
            'has_customization' => $groups->isNotEmpty(),
            'has_variations' => $groups->isNotEmpty(),
            'choice_groups' => $groups->where('type', ProductVariationGroup::TYPE_CHOICE)->values(),
            'addon_groups' => $groups->where('type', ProductVariationGroup::TYPE_ADDON)->values(),
            'disposable_groups' => $groups->where('type', ProductVariationGroup::TYPE_DISPOSABLE)->values(),
            'variation_groups' => $groups,
        ];
    }

    protected function groupPayload($group): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'type' => $group->type ?? ProductVariationGroup::TYPE_CHOICE,
            'min_select' => $group->min_select,
            'max_select' => $group->max_select,
            'allow_quantity' => (bool) ($group->allow_quantity ?? ($group->type === ProductVariationGroup::TYPE_DISPOSABLE)),
            'options' => $group->options->map(fn ($o) => [
                'id' => $o->id,
                'name' => $o->name,
                'additional_price' => $o->additional_price,
                'max_quantity' => (int) ($o->max_quantity ?? ($group->allow_quantity ? max(1, $group->max_select) : 1)),
            ]),
        ];
    }

    public function branchPayload(Branch $branch, ?Tenant $tenant = null): array
    {
        $tenant ??= TenantContext::get();
        $tenantInstagram = $tenant?->social_links['instagram'] ?? null;

        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'slug' => $branch->slug,
            'public_description' => $branch->public_description,
            'full_address' => $branch->fullAddress(),
            'is_open' => $branch->isOpenNow(),
            'can_order' => $branch->isOpenNow(),
            'guest_checkout_enabled' => TenantOrderSettings::guestCheckoutEnabled($tenant),
            'pickup_available' => $branch->pickup_available,
            'delivery_available' => $branch->delivery_available,
            'delivery_radius_km' => $branch->delivery_radius_km,
            'minimum_order_amount' => $branch->minimum_order_amount,
            'packaging_fee_default' => $branch->packaging_fee_default,
            'default_prep_time_minutes' => (int) ($branch->default_prep_time_minutes ?: 30),
            'delivery_time_minutes' => (int) ($branch->delivery_time_minutes ?? 0),
            'allow_scheduled_orders' => (bool) $branch->allow_scheduled_orders,
            'order_disposables' => OrderDisposableConfig::normalizeList($branch->order_disposables),
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'has_per_km_delivery' => DeliveryQuoteService::branchUsesPerKmPricing($branch),
            'phone' => $branch->phone,
            'instagram' => InstagramLink::resolve($branch->instagram, $tenantInstagram),
            'cover_url' => MediaUrl::fromPath($branch->cover_image_path),
        ];
    }
}
