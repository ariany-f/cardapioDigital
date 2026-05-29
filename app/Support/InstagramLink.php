<?php

namespace App\Support;

class InstagramLink
{
    /**
     * @return array{handle: string, label: string, url: string}|null
     */
    public static function resolve(?string $branchInstagram, ?string $tenantInstagram): ?array
    {
        $raw = self::nonEmpty($branchInstagram) ?? self::nonEmpty($tenantInstagram);

        if ($raw === null) {
            return null;
        }

        $handle = self::normalizeHandle($raw);
        $url = self::toUrl($raw);

        if ($handle === null || $url === null) {
            return null;
        }

        return [
            'handle' => $handle,
            'label' => '@'.$handle,
            'url' => $url,
        ];
    }

    public static function normalizeHandle(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('#instagram\.com/([A-Za-z0-9._]+)#i', $value, $matches)) {
            return $matches[1];
        }

        return ltrim($value, '@') ?: null;
    }

    public static function toUrl(?string $value): ?string
    {
        $handle = self::normalizeHandle($value);

        if ($handle === null) {
            return null;
        }

        if (str_starts_with(strtolower(trim($value)), 'http')) {
            return trim($value);
        }

        return 'https://www.instagram.com/'.$handle;
    }

    protected static function nonEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
