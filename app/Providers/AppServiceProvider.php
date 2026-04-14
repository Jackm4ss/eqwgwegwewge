<?php

namespace App\Providers;

use App\Contracts\UserRepositoryInterface;
use App\Repositories\FirestoreRestUserRepository;
use App\Repositories\FirestoreUserRepository;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Firebase\FirebaseClientFactory;
use App\Services\Firebase\FirestoreRestApi;
use App\Services\Scanner\ScannerGateService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            FirebaseClientFactory::class,
            fn () => new FirebaseClientFactory(config('firebase'))
        );

        $this->app->singleton(
            FirestoreRestApi::class,
            fn () => new FirestoreRestApi(config('firebase'))
        );

        $this->app->singleton(AdminFirestoreRepository::class);

        $this->app->bind(
            UserRepositoryInterface::class,
            function ($app) {
                return match ((string) config('firebase.transport', 'grpc')) {
                    'grpc' => $app->make(FirestoreUserRepository::class),
                    'rest' => $app->make(FirestoreRestUserRepository::class),
                    default => throw new RuntimeException('Unsupported Firestore transport configuration.'),
                };
            }
        );
    }

    public function boot(): void
    {
        View::composer('welcome', function ($view): void {
            $view->with('staffScannerPosts', app(ScannerGateService::class)->names());
        });

        RateLimiter::for('register-api', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('resend-verification', function (Request $request) {
            return Limit::perMinute(3)->by(
                $request->ip().'|'.$request->input('email')
            );
        });

        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(5)->by(
                strtolower((string) $request->input('email')).'|'.$request->ip()
            );
        });

        RateLimiter::for('forgot-qr-lookup', function (Request $request) {
            $searchType = strtolower(trim((string) $request->input('search_type', '')));

            $normalizePhoneCountryCode = static function (string $phoneCountryCode): string {
                $digits = preg_replace('/\D+/', '', $phoneCountryCode) ?? '';

                return $digits === '' ? '' : '+'.$digits;
            };

            $normalizePhoneNationalNumber = static function (string $phoneNationalNumber): string {
                $digits = preg_replace('/\D+/', '', $phoneNationalNumber) ?? '';

                return ltrim($digits, '0');
            };

            $identifier = match ($searchType) {
                'email' => strtolower(trim((string) $request->input('email', ''))),
                'phone' => $normalizePhoneCountryCode((string) $request->input('phone_country_code', ''))
                    .$normalizePhoneNationalNumber((string) $request->input('phone_national_number', '')),
                'passport' => strtoupper(trim((string) $request->input('country', '')))
                    .':'
                    .strtoupper(trim((string) $request->input('identity_number', ''))),
                'ic' => 'MY:'.strtoupper(trim((string) $request->input('identity_number', ''))),
                default => '',
            };

            if ($identifier === '') {
                $identifier = 'missing';
            }

            return [
                Limit::perMinute(8)->by($request->ip()),
                Limit::perMinute(3)->by($request->ip().'|'.$searchType.'|'.$identifier),
            ];
        });

        RateLimiter::for('public-report-submit', function (Request $request) {
            $identifier = strtolower(trim((string) $request->input('email', '')))
                .'|'
                .preg_replace('/\D+/', '', (string) $request->input('phone', ''));

            if ($identifier === '|') {
                $identifier = 'guest';
            }

            return [
                Limit::perMinute(6)->by($request->ip()),
                Limit::perMinute(2)->by($request->ip().'|'.$identifier),
            ];
        });

        RateLimiter::for('public-report-lookup', function (Request $request) {
            $reference = strtoupper(trim((string) $request->input('reference', '')));

            if ($reference === '') {
                $reference = 'missing-reference';
            }

            return [
                Limit::perMinute(8)->by($request->ip()),
                Limit::perMinute(3)->by($request->ip().'|'.$reference),
            ];
        });

        RateLimiter::for('traffic-visit', function (Request $request) {
            $landingPath = trim((string) $request->input('traffic_landing_path', ''));

            if ($landingPath === '') {
                $landingPath = '/';
            }

            return [
                Limit::perMinute(20)->by($request->ip()),
                Limit::perMinute(6)->by($request->ip().'|'.$landingPath),
            ];
        });

        RateLimiter::for('found-items-read', function (Request $request) {
            $page = max(1, (int) $request->query('page', 1));

            return [
                Limit::perMinute(60)->by($request->ip()),
                Limit::perMinute(20)->by($request->ip().'|page:'.$page),
            ];
        });
    }
}
