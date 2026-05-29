<?php

namespace App\Support;

use App\Models\PlatformStorageSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PlatformStorage
{
    public static function uploadDisk(): string
    {
        if (! Schema::hasTable('platform_storage_settings')) {
            return 'public';
        }

        $settings = PlatformStorageSetting::current();

        return $settings->isConfigured() ? 's3' : 'public';
    }

    public static function usesS3(): bool
    {
        return self::uploadDisk() === 's3';
    }

    /**
     * @return list<string>
     */
    public static function disksForPath(): array
    {
        return self::usesS3() ? ['s3', 'public'] : ['public'];
    }

    public static function deletePath(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        foreach (self::disksForPath() as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);

                return;
            }
        }
    }

    public static function urlForPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        foreach (self::disksForPath() as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->url($path);
            }
        }

        return asset('storage/'.$path);
    }
}
