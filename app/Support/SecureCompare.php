<?php

namespace App\Support;

final class SecureCompare
{
    public static function equals(?string $known, ?string $user): bool
    {
        if ($known === null || $user === null) {
            return false;
        }

        if ($known === '' || $user === '') {
            return false;
        }

        return hash_equals($known, $user);
    }
}
