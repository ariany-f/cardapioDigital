<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TenantLogoStorage
{
    public static function store(UploadedFile $file, int $tenantId): string
    {
        return $file->store("tenants/{$tenantId}/logo", 'public');
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
