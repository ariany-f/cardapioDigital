<?php

namespace App\Services\Storage;

use App\Models\PlatformStorageSetting;
use Illuminate\Support\Facades\Schema;

class PlatformStorageConfigurator
{
    public function apply(): void
    {
        if (! Schema::hasTable('platform_storage_settings')) {
            return;
        }

        $settings = PlatformStorageSetting::current();

        if (! $settings->isConfigured()) {
            return;
        }

        config([
            'filesystems.disks.s3.key' => $settings->key,
            'filesystems.disks.s3.secret' => $settings->secret,
            'filesystems.disks.s3.region' => $settings->region,
            'filesystems.disks.s3.bucket' => $settings->bucket,
            'filesystems.disks.s3.url' => filled($settings->url) ? $settings->url : null,
            'filesystems.disks.s3.endpoint' => filled($settings->endpoint) ? $settings->endpoint : null,
            'filesystems.disks.s3.use_path_style_endpoint' => (bool) $settings->use_path_style_endpoint,
            'filesystems.default' => 's3',
        ]);
    }
}
