<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TenantLogoStorage
{
    public static function store(UploadedFile $file, int $tenantId): string
    {
        return $file->store("tenants/{$tenantId}/logo", [
            'disk' => PlatformStorage::uploadDisk(),
            'visibility' => 'public',
        ]);
    }

    public static function delete(?string $path): void
    {
        PlatformStorage::deletePath($path);
    }
}
