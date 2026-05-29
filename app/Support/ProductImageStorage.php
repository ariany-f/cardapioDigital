<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageStorage
{
    public static function store(UploadedFile $file, int $tenantId, int $productId): string
    {
        return $file->store("tenants/{$tenantId}/products/{$productId}", 'public');
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
