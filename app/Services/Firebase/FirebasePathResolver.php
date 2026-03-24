<?php

namespace App\Services\Firebase;

class FirebasePathResolver
{
    public static function credentialsPath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (self::isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
