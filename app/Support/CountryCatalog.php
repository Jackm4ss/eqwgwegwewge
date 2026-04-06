<?php

namespace App\Support;

final class CountryCatalog
{
    private const PRIORITY_CODES = ['MY', 'TH', 'SG', 'ID', 'BN', 'MM', 'VN'];

    private const REGISTER_EXCLUDED_CODES = ['IL'];

    private static ?array $entries = null;

    private static ?array $searchEntries = null;

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

    public static function dialCodesFor(mixed $countryCode): array
    {
        $countryCode = strtoupper(trim((string) $countryCode));

        if ($countryCode === '') {
            return [];
        }

        $searchEntry = self::searchEntriesByCode()[$countryCode] ?? null;
        $dialCodes = array_values(array_filter(
            $searchEntry['dial_codes'] ?? [],
            static fn (mixed $dialCode): bool => trim((string) $dialCode) !== '',
        ));

        if ($dialCodes !== []) {
            return $dialCodes;
        }

        $dialCode = self::dialCodeFor($countryCode);

        return $dialCode !== '' ? [$dialCode] : [];
    }

    public static function registrationCountries(): array
    {
        return array_map(
            static fn (array $entry): array => self::mapCountryOption($entry),
            self::registrationCountryEntries(),
        );
    }

    public static function registrationCountryGroups(): array
    {
        $countries = self::registrationCountries();

        return [
            'priority' => array_values(array_filter(
                $countries,
                static fn (array $country): bool => ($country['group'] ?? 'other') === 'priority',
            )),
            'other' => array_values(array_filter(
                $countries,
                static fn (array $country): bool => ($country['group'] ?? 'other') === 'other',
            )),
        ];
    }

    public static function registrationPhoneOptions(): array
    {
        $options = [];

        foreach (self::registrationCountryEntries() as $entry) {
            $dialCodes = array_values(array_filter(
                $entry['dial_codes'] ?? [],
                static fn (mixed $dialCode): bool => trim((string) $dialCode) !== '',
            ));

            foreach ($dialCodes as $dialCode) {
                $options[] = [
                    'value' => (string) $dialCode,
                    'key' => strtoupper((string) $entry['code']).':'.(string) $dialCode,
                    'country_code' => strtoupper((string) $entry['code']),
                    'alpha3' => strtoupper((string) ($entry['alpha3'] ?? '')),
                    'name' => trim((string) ($entry['name'] ?? '')),
                    'dial_code' => (string) $dialCode,
                    'flag' => strtolower((string) ($entry['code'] ?? 'xx')),
                    'group' => in_array(strtoupper((string) $entry['code']), self::PRIORITY_CODES, true) ? 'priority' : 'other',
                ];
            }
        }

        return $options;
    }

    public static function registrationPhoneGroups(): array
    {
        $options = self::registrationPhoneOptions();

        return [
            'priority' => array_values(array_filter(
                $options,
                static fn (array $option): bool => ($option['group'] ?? 'other') === 'priority',
            )),
            'other' => array_values(array_filter(
                $options,
                static fn (array $option): bool => ($option['group'] ?? 'other') === 'other',
            )),
        ];
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

    private static function registrationCountryEntries(): array
    {
        $searchEntriesByCode = self::searchEntriesByCode();
        $excludedCodes = array_flip(self::REGISTER_EXCLUDED_CODES);
        $priorityEntries = [];

        foreach (self::PRIORITY_CODES as $priorityCode) {
            if (isset($excludedCodes[$priorityCode])) {
                continue;
            }

            $priorityEntry = $searchEntriesByCode[$priorityCode] ?? null;

            if ($priorityEntry !== null) {
                $priorityEntries[] = $priorityEntry;
            }
        }

        $otherEntries = array_values(array_filter(
            self::searchEntries(),
            static function (array $entry) use ($excludedCodes): bool {
                $code = strtoupper((string) ($entry['code'] ?? ''));

                return ! isset($excludedCodes[$code]) && ! in_array($code, self::PRIORITY_CODES, true);
            },
        ));

        return [...$priorityEntries, ...$otherEntries];
    }

    private static function mapCountryOption(array $entry): array
    {
        $code = strtoupper((string) ($entry['code'] ?? ''));

        return [
            'value' => $code,
            'code' => $code,
            'alpha3' => strtoupper((string) ($entry['alpha3'] ?? '')),
            'name' => trim((string) ($entry['name'] ?? '')),
            'flag' => strtolower($code),
            'group' => in_array($code, self::PRIORITY_CODES, true) ? 'priority' : 'other',
        ];
    }

    private static function searchEntries(): array
    {
        if (self::$searchEntries !== null) {
            return self::$searchEntries;
        }

        $path = base_path('resources/data/country-search-catalog.json');

        if (! is_file($path)) {
            return self::$searchEntries = [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return self::$searchEntries = [];
        }

        $entries = [];

        foreach ($decoded as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $code = strtoupper(trim((string) ($entry['code'] ?? '')));
            $alpha3 = strtoupper(trim((string) ($entry['alpha3'] ?? '')));
            $name = trim((string) ($entry['name'] ?? ''));
            $dialCode = trim((string) ($entry['dialCode'] ?? ''));
            $dialCodes = array_values(array_filter(
                array_map(
                    static fn (mixed $value): string => trim((string) $value),
                    is_array($entry['dialCodes'] ?? null) ? $entry['dialCodes'] : []
                ),
                static fn (string $value): bool => $value !== '',
            ));

            if ($code === '' || $alpha3 === '' || $name === '') {
                continue;
            }

            $entries[] = [
                'code' => $code,
                'alpha3' => $alpha3,
                'name' => $name,
                'dial_code' => $dialCode,
                'dial_codes' => $dialCodes === [] && $dialCode !== '' ? [$dialCode] : $dialCodes,
            ];
        }

        return self::$searchEntries = $entries;
    }

    private static function searchEntriesByCode(): array
    {
        return collect(self::searchEntries())
            ->keyBy(static fn (array $entry): string => strtoupper((string) $entry['code']))
            ->all();
    }
}
