<?php

namespace App\Support;

class LostFoundContactFormatter
{
    public const PUBLIC_NAME = 'Songkran Help Desk';

    public const PUBLIC_LABEL = 'WhatsApp / Phone Number';

    public const DEFAULT_COUNTRY_CODE = '+60';

    public const DEFAULT_COUNTRY_LABEL = 'Malaysia +60';

    public static function normalizeForStorage(?string $value): string
    {
        return self::extractPrimaryContact($value);
    }

    public static function normalizeFromParts(?string $countryCode, ?string $phoneNumber): string
    {
        $normalizedCountryCode = trim((string) $countryCode);
        $normalizedPhoneNumber = preg_replace('/\D+/u', '', (string) $phoneNumber) ?? '';
        $normalizedPhoneNumber = ltrim($normalizedPhoneNumber, '0');

        if ($normalizedCountryCode === '') {
            $normalizedCountryCode = self::DEFAULT_COUNTRY_CODE;
        }

        if ($normalizedPhoneNumber === '') {
            return '';
        }

        return trim($normalizedCountryCode.' '.$normalizedPhoneNumber);
    }

    public static function adminDisplayValue(?string $value): string
    {
        return self::extractPrimaryContact($value);
    }

    public static function adminCountryCode(?string $value): string
    {
        $contact = self::extractPrimaryContact($value);

        if (preg_match('/^(\+\d{1,4})(?:\s+|$)/', $contact, $matches) === 1) {
            return (string) $matches[1];
        }

        return self::DEFAULT_COUNTRY_CODE;
    }

    public static function adminPhoneNumber(?string $value): string
    {
        $contact = self::extractPrimaryContact($value);
        $countryCode = self::adminCountryCode($contact);
        $withoutCountryCode = trim((string) preg_replace('/^'.preg_quote($countryCode, '/').'\s*/', '', $contact));

        return preg_replace('/\D+/u', '', $withoutCountryCode) ?? '';
    }

    public static function formatForPublic(?string $value): string
    {
        return self::extractPrimaryContact($value);
    }

    private static function extractPrimaryContact(?string $value): string
    {
        $normalized = trim(str_replace(["\r\n", "\r"], "\n", (string) $value));

        if ($normalized === '') {
            return '';
        }

        if (preg_match('/(\+?\d[\d\s().-]{5,}\d)/', $normalized, $matches) === 1) {
            return trim((string) preg_replace('/\s+/u', ' ', trim((string) $matches[1])));
        }

        $lines = preg_split('/\n+/u', $normalized) ?: [];

        foreach ($lines as $line) {
            $cleanLine = self::stripKnownPrefixes(trim((string) $line));

            if ($cleanLine !== '' && strcasecmp($cleanLine, self::PUBLIC_NAME) !== 0) {
                return trim((string) preg_replace('/\s+/u', ' ', $cleanLine));
            }
        }

        return trim((string) preg_replace('/\s+/u', ' ', self::stripKnownPrefixes($normalized)));
    }

    private static function stripKnownPrefixes(string $value): string
    {
        $patterns = [
            '/^songkran help desk[\s:,-]*/iu',
            '/^whats?app\s*\/\s*phone\s*number\s*:\s*/iu',
            '/^whats?app\s*:\s*/iu',
            '/^whats?app\s+/iu',
            '/^phone\s*number\s*:\s*/iu',
            '/^call\s*:\s*/iu',
            '/^call\s+/iu',
        ];

        return trim((string) preg_replace($patterns, '', $value));
    }
}
