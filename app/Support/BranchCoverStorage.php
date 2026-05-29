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

        return $file->storeAs("tenants/{$tenantId}/branches/{$branchSlug}", $name, 'public');
    }

    public static function delete(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
