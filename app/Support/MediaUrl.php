<?php

namespace App\Support;

class MediaUrl
{
    public static function fromPath(?string $path): ?string
    {
        return PlatformStorage::urlForPath($path);
    }
}
