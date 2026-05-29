<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Tenant;
use App\Support\SecureImageUpload;
use App\Support\TenantLogoStorage;
use Illuminate\Http\Request;

trait HandlesTenantLogoUpload
{
    protected function logoImageRules(): array
    {
        return SecureImageUpload::rules('logo');
    }

    protected function storeTenantLogo(Request $request, Tenant $tenant): void
    {
        if (! $request->hasFile('logo')) {
            return;
        }

        TenantLogoStorage::delete($tenant->logo_path);

        $tenant->update([
            'logo_path' => TenantLogoStorage::store($request->file('logo'), $tenant->id),
        ]);
    }
}
