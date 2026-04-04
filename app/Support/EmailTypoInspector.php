<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Silassiai\LaravelEmailValidation\Validation\EmailValidation as PackageEmailValidation;

final class EmailTypoInspector
{
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $fakeResults = null;

    public static function analyze(?string $email): array
    {
        $normalizedEmail = self::normalizeEmail($email);

        if ($normalizedEmail === '') {
            return self::cleanResult(null);
        }

        self::preload([$normalizedEmail]);

        return self::$cache[$normalizedEmail] ?? self::cleanResult($normalizedEmail);
    }

    public static function preload(array $emails): void
    {
        $normalizedEmails = array_values(array_unique(array_filter(array_map(
            fn (mixed $email): string => self::normalizeEmail($email),
            $emails,
        ))));

        if ($normalizedEmails === []) {
            return;
        }

        $missingEmails = array_values(array_filter(
            $normalizedEmails,
            fn (string $email): bool => ! array_key_exists($email, self::$cache),
        ));

        if ($missingEmails === []) {
            return;
        }

        if (is_array(self::$fakeResults)) {
            foreach ($missingEmails as $email) {
                self::$cache[$email] = self::normalizeResult(
                    $email,
                    self::$fakeResults[$email] ?? [],
                );
            }

            return;
        }

        foreach ($missingEmails as $email) {
            self::$cache[$email] = self::resolveWithPackage($email);
        }
    }

    public static function fake(array $results): void
    {
        self::$fakeResults = [];
        self::$cache = [];

        foreach ($results as $email => $result) {
            $normalizedEmail = self::normalizeEmail($email);

            if ($normalizedEmail === '') {
                continue;
            }

            self::$fakeResults[$normalizedEmail] = self::normalizeResult(
                $normalizedEmail,
                is_array($result) ? $result : [],
            );
        }
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    public static function clearFakes(): void
    {
        self::$fakeResults = null;
        self::$cache = [];
    }

    private static function normalizeEmail(mixed $email): string
    {
        return strtolower(trim((string) $email));
    }

    private static function storeCleanResults(array $emails): void
    {
        foreach ($emails as $email) {
            self::$cache[$email] = self::cleanResult($email);
        }
    }

    private static function resolveWithPackage(string $email): array
    {
        try {
            /** @var PackageEmailValidation $validator */
            $validator = app(PackageEmailValidation::class);
            $typo = $validator->for($email)->hasTypo();

            if (! is_string($typo) || trim($typo) === '') {
                return self::cleanResult($email);
            }

            $suggestedDomain = str_contains($typo, '.')
                ? strtolower(trim($typo))
                : null;

            return self::normalizeResult($email, [
                'status' => 'suspected_typo',
                'suspected' => true,
                'suggested_domain' => $suggestedDomain,
                'suggested_email' => $suggestedDomain !== null
                    ? self::replaceDomain($email, $suggestedDomain)
                    : null,
                'original_domain' => self::extractDomain($email),
                'reason' => $suggestedDomain !== null
                    ? 'silassiai_typo'
                    : 'silassiai_domain_hint',
            ]);
        } catch (\Throwable $throwable) {
            Log::warning('silassiai/laravel-email-validation lookup failed; falling back to clean result.', [
                'email' => $email,
                'message' => $throwable->getMessage(),
            ]);

            return self::cleanResult($email);
        }
    }

    private static function cleanResult(?string $email): array
    {
        return [
            'status' => 'clean',
            'suspected' => false,
            'suggestion' => null,
            'suggested_email' => null,
            'suggested_domain' => null,
            'original_domain' => self::extractDomain($email),
            'reason' => null,
        ];
    }

    private static function normalizeResult(string $email, array $result): array
    {
        $suggestedEmail = trim((string) ($result['suggested_email'] ?? $result['suggestion'] ?? ''));
        $suggestedDomain = trim((string) ($result['suggested_domain'] ?? ''));
        $suspected = (bool) ($result['suspected'] ?? ($suggestedEmail !== ''));
        $status = trim((string) ($result['status'] ?? ($suspected ? 'suspected_typo' : 'clean')));

        if ($suggestedEmail === '' && $suggestedDomain !== '' && str_contains($email, '@')) {
            [$localPart] = explode('@', $email, 2);
            $suggestedEmail = $localPart.'@'.$suggestedDomain;
        }

        return [
            'status' => $status !== '' ? $status : ($suspected ? 'suspected_typo' : 'clean'),
            'suspected' => $suspected,
            'suggestion' => $suggestedEmail !== '' ? $suggestedEmail : null,
            'suggested_email' => $suggestedEmail !== '' ? $suggestedEmail : null,
            'suggested_domain' => $suggestedDomain !== '' ? $suggestedDomain : self::extractDomain($suggestedEmail),
            'original_domain' => trim((string) ($result['original_domain'] ?? self::extractDomain($email))),
            'reason' => filled($result['reason'] ?? null)
                ? (string) $result['reason']
                : null,
        ];
    }

    private static function extractDomain(?string $email): ?string
    {
        $email = trim((string) $email);

        if ($email === '' || ! str_contains($email, '@')) {
            return null;
        }

        [, $domain] = explode('@', $email, 2);

        return $domain !== '' ? $domain : null;
    }

    private static function replaceDomain(string $email, string $domain): string
    {
        [$localPart] = explode('@', $email, 2);

        return $localPart.'@'.$domain;
    }
}
