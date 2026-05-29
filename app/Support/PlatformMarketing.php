<?php

namespace App\Support;

use App\Models\PlatformSeoSetting;
use Illuminate\Support\Facades\Schema;

class PlatformMarketing
{
    public static function landingEnabled(): bool
    {
        if (! Schema::hasTable('platform_seo_settings')
            || ! Schema::hasColumn('platform_seo_settings', 'marketing_landing_enabled')) {
            return true;
        }

        return (bool) PlatformSeoSetting::current()->marketing_landing_enabled;
    }
}
