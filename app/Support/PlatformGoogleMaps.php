<?php

namespace App\Support;

use App\Models\PlatformGoogleMapsSetting;
use Illuminate\Support\Facades\Schema;

class PlatformGoogleMaps
{
    public static function settings(): PlatformGoogleMapsSetting
    {
        if (! Schema::hasTable('platform_google_maps_settings')) {
            return new PlatformGoogleMapsSetting([
                'enabled' => false,
            ]);
        }

        return PlatformGoogleMapsSetting::current();
    }

    public static function isConfigured(): bool
    {
        return static::settings()->isConfigured();
    }

    public static function apiKey(): ?string
    {
        $settings = static::settings();

        return $settings->isConfigured() ? $settings->api_key : null;
    }

    /**
     * @return array{enabled: bool, api_key: string}|null
     */
    public static function forFrontend(): ?array
    {
        if (! Schema::hasTable('platform_google_maps_settings')) {
            return null;
        }

        return PlatformGoogleMapsSetting::current()->toPublicArray();
    }
}
