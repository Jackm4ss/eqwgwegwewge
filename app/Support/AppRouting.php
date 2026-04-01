<?php

namespace App\Support;

final class AppRouting
{
    public static function mode(): string
    {
        return config('routing.mode') === 'subdomain' ? 'subdomain' : 'path';
    }

    public static function isSubdomainMode(): bool
    {
        return self::mode() === 'subdomain';
    }

    public static function adminPath(): string
    {
        return trim((string) config('admin.path', 'admin'), '/');
    }

    public static function staffPath(): string
    {
        return trim((string) config('scanner.path', 'staff'), '/');
    }

    public static function publicUrl(): string
    {
        return (string) (config('routing.public_url') ?: config('app.url'));
    }

    public static function adminUrl(): string
    {
        return (string) (config('routing.admin_url') ?: config('app.url'));
    }

    public static function helpUrl(): string
    {
        return (string) (config('routing.help_url') ?: config('app.url'));
    }

    public static function staffUrl(): string
    {
        return (string) (config('routing.staff_url') ?: config('app.url'));
    }

    public static function registerUrl(): string
    {
        return (string) (
            config('routing.register_url')
            ?: config('admin.future_urls.register')
            ?: config('app.url')
        );
    }

    public static function urlForArea(string $area): string
    {
        return match ($area) {
            'admin' => self::adminUrl(),
            'help' => self::helpUrl(),
            'register' => self::registerUrl(),
            'staff' => self::staffUrl(),
            default => self::publicUrl(),
        };
    }

    public static function hostFor(string $area): ?string
    {
        if (! self::isSubdomainMode()) {
            return null;
        }

        $host = parse_url(self::urlForArea($area), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }

    public static function normalizePath(?string $path): string
    {
        $normalized = trim((string) $path, '/');

        return $normalized === '' ? '/' : '/'.$normalized;
    }

    public static function routePath(string $routeName): string
    {
        $path = parse_url(route($routeName), PHP_URL_PATH);

        return self::normalizePath(is_string($path) ? $path : '/');
    }

    public static function spaConfig(string $context): array
    {
        return [
            'context' => $context,
            'pwa' => [
                'enabled' => $context === 'staff',
                'manifestUrl' => route('pwa.staff.manifest'),
            ],
            'paths' => match ($context) {
                'admin' => [
                    'adminLogin' => [self::routePath('login')],
                ],
                'staff' => [
                    'staffLogin' => [self::routePath('staff.login')],
                    'staffHome' => [self::routePath('staff.home')],
                ],
                default => [
                    'landing' => ['/'],
                    'register' => [self::routePath('register.form')],
                    'forgotQr' => [self::routePath('forgot-qr.form')],
                    'report' => [self::routePath('report.form')],
                ],
            },
            'urls' => [
                'publicHome' => route('landing.home'),
                'registerForm' => route('register.form'),
                'registerApi' => url('/api/register'),
                'forgotQrLookupApi' => url('/api/forgot-qr/lookup'),
                'reportSubmitApi' => url('/api/report'),
                'forgotPassword' => route('password.request'),
                'login' => route('login'),
                'adminLoginSubmit' => route('admin.login.store'),
                'adminDashboard' => route('admin.dashboard'),
                'staffLogin' => route('staff.login'),
                'staffHome' => route('staff.home'),
                'staffLoginSubmit' => route('staff.login.store'),
                'staffLogout' => route('staff.logout'),
                'staffSession' => route('staff.session.show'),
                'staffScannerPost' => route('staff.session.scanner-post'),
                'staffScan' => route('staff.scan'),
                'staffManualLookup' => route('staff.manual-lookup'),
                'staffManualConfirm' => route('staff.manual-confirm'),
                'staffDashboard' => route('staff.dashboard'),
                'staffHistory' => route('staff.history'),
                'staffStats' => route('staff.stats'),
            ],
        ];
    }
}
