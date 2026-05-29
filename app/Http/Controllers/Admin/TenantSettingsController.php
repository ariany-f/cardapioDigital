<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTenantLogoUpload;
use App\Http\Controllers\Controller;
use App\Support\MediaUrl;
use App\Support\TenantContext;
use App\Support\TenantDeliverySettings;
use App\Support\TenantFeatures;
use App\Support\TenantOrderSettings;
use App\Support\TenantPaymentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantSettingsController extends Controller
{
    use HandlesTenantLogoUpload;

    public function edit(): Response
    {
        $tenant = TenantContext::get();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'name' => $tenant->name,
                'public_description' => $tenant->public_description,
                'phone' => $tenant->phone,
                'whatsapp' => $tenant->whatsapp,
                'website' => $tenant->website,
                'instagram' => $tenant->social_links['instagram'] ?? '',
                'theme_primary_color' => $tenant->theme_primary_color ?? '#ea580c',
                'theme_secondary_color' => $tenant->theme_secondary_color ?? '#1f2937',
                'logo_url' => MediaUrl::fromPath($tenant->logo_path),
            ],
            'deliverySettings' => TenantDeliverySettings::from($tenant),
            'paymentSettings' => TenantPaymentSettings::from($tenant),
            'orderSettings' => TenantOrderSettings::from($tenant),
            'motoboys_enabled' => TenantFeatures::motoboysEnabled($tenant),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = TenantContext::get();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'public_description' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:100'],
            'theme_primary_color' => ['nullable', 'string', 'max:20'],
            'theme_secondary_color' => ['nullable', 'string', 'max:20'],
            ...$this->logoImageRules(),
        ]);

        $social = $tenant->social_links ?? [];
        if (! empty($data['instagram'])) {
            $social['instagram'] = ltrim($data['instagram'], '@');
        } else {
            unset($social['instagram']);
        }

        $tenant->update([
            'name' => $data['name'],
            'public_description' => $data['public_description'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'website' => $data['website'] ?? null,
            'social_links' => $social ?: null,
            'theme_primary_color' => $data['theme_primary_color'] ?? '#ea580c',
            'theme_secondary_color' => $data['theme_secondary_color'] ?? '#1f2937',
        ]);

        $this->storeTenantLogo($request, $tenant);

        if (TenantFeatures::motoboysEnabled($tenant)) {
            TenantDeliverySettings::merge($tenant, [
                'motoboy_auto_accept_assignments' => $request->boolean('motoboy_auto_accept_assignments'),
            ]);
        }

        TenantOrderSettings::merge($tenant, [
            'guest_checkout_enabled' => $request->boolean('guest_checkout_enabled'),
        ]);

        TenantPaymentSettings::merge($tenant, [
            'pix_enabled' => $request->boolean('pix_enabled'),
            'pix_key_type' => $request->input('pix_key_type', 'phone'),
            'pix_key' => $request->input('pix_key'),
            'pix_beneficiary' => $request->input('pix_beneficiary'),
            'card_online_enabled' => $request->boolean('card_online_enabled'),
            'card_online_instructions' => $request->input('card_online_instructions'),
        ]);

        return back()->with('success', 'Configurações salvas.');
    }
}
