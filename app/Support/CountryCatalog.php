<?php

namespace App\Support;

final class CountryCatalog
{
    private static ?array $entries = null;

    public static function all(): array
    {
        return array_values(self::entriesByCode());
    }

    public static function codes(): array
    {
        return array_keys(self::entriesByCode());
    }

    public static function nameFor(mixed $countryCode): ?string
    {
        $countryCode = strtoupper(trim((string) $countryCode));

        if ($countryCode === '') {
            return null;
        }

        return self::entriesByCode()[$countryCode]['name'] ?? null;
    }

    public static function dialCodeFor(mixed $countryCode): string
    {
        $countryCode = strtoupper(trim((string) $countryCode));

        if ($countryCode === '') {
            return '';
        }

        return (string) (self::entriesByCode()[$countryCode]['dial_code'] ?? '');
    }

    private static function entriesByCode(): array
    {
        if (self::$entries !== null) {
            return self::$entries;
        }

        $path = base_path('resources/data/country-catalog.json');

        if (! is_file($path)) {
            return self::$entries = [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return self::$entries = [];
        }

        $entries = [];

        foreach ($decoded as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $code = strtoupper(trim((string) ($entry['code'] ?? '')));
            $name = trim((string) ($entry['name'] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            $entries[$code] = [
                'code' => $code,
                'name' => $name,
                'dial_code' => trim((string) ($entry['dialCode'] ?? '')),
            ];
        }

        return self::$entries = $entries;
    }
}
