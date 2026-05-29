<?php

namespace App\Http\Middleware;

use App\Models\Language;
use App\Support\BranchAccess;
use App\Support\TenantContext;
use App\Support\PlatformCommunicationDisclaimer;
use App\Support\TenantFeatures;
use App\Support\TenantOrderSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $tenant = TenantContext::get();

        $locale = $request->session()->get('locale', config('app.locale'));
        $activeLocales = Language::query()->where('is_active', true)->pluck('code')->all();
        if (! in_array($locale, $activeLocales, true)) {
            $locale = Language::query()->where('is_default', true)->value('code') ?? 'pt_BR';
        }

        $translations = [];
        $langFile = lang_path($locale.'.json');
        if (! file_exists($langFile)) {
            $langFile = lang_path('pt_BR.json');
        }
        if (file_exists($langFile)) {
            $translations = json_decode(file_get_contents($langFile), true) ?? [];
        }

        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name', 'flag', 'is_default']);

        $user = $request->user('web');
        $customer = $request->user('customer');

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_platform_user' => (bool) $user->is_platform_user,
                    'access_all_branches' => (bool) $user->access_all_branches,
                    'allowed_branch_ids' => BranchAccess::allowedBranchIds($user),
                    'branch_scope_limited' => ! BranchAccess::hasUnrestrictedBranchAccess($user),
                ] : null,
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                ] : null,
                'permissions' => $user && ! $user->is_platform_user
                    ? $user->getAllPermissions()->pluck('name')->values()->all()
                    : ($user?->is_platform_user ? ['*'] : []),
            ],
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo_path' => $tenant->logo_path,
                'theme_primary_color' => $tenant->theme_primary_color,
                'theme_secondary_color' => $tenant->theme_secondary_color,
                'public_description' => $tenant->public_description,
                'motoboys_enabled' => TenantFeatures::motoboysEnabled($tenant),
                'pos_enabled' => TenantFeatures::posEnabled($tenant),
                'kds_enabled' => TenantFeatures::kdsEnabled($tenant),
                'guest_checkout_enabled' => TenantOrderSettings::guestCheckoutEnabled($tenant),
            ] : null,
            'locale' => $locale,
            'languages' => $languages,
            'translations' => $translations,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'guest_access_code' => fn () => $request->session()->get('guest_access_code'),
            ],
            'communication_disclaimer' => PlatformCommunicationDisclaimer::forInertia($tenant?->name),
        ];
    }
}
