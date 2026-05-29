<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BranchCoverStorage
{
    public static function store(UploadedFile $file, int $tenantId, string $branchSlug): string
    {
        $name = 'hero-'.Str::uuid().'.'.$file->getClientOriginalExtension();

        return $file->storeAs("tenants/{$tenantId}/branches/{$branchSlug}", $name, [
            'disk' => PlatformStorage::uploadDisk(),
            'visibility' => 'public',
        ]);
    }

    public static function delete(?string $path): void
    {
        PlatformStorage::deletePath($path);
    }
}
