<?php

namespace App\Support;

final class BootstrapAccountEmail
{
    public static function admin(int $index): string
    {
        return self::build('admin', $index);
    }

    public static function scanner(int $index): string
    {
        return self::build('scanner', $index);
    }

    public static function domain(): string
    {
        $configuredDomain = trim((string) (
            config('admin.seed_email_domain')
            ?: config('scanner.seed_email_domain')
            ?: 'songkran.local'
        ));

        $normalizedDomain = strtolower(ltrim($configuredDomain, '@'));

        return $normalizedDomain !== '' ? $normalizedDomain : 'songkran.local';
    }

    private static function build(string $prefix, int $index): string
    {
        return sprintf('%s%02d@%s', $prefix, $index, self::domain());
    }
}
