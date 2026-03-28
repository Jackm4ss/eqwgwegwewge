<?php

namespace App\Providers;

use App\Contracts\UserRepositoryInterface;
use App\Repositories\FirestoreRestUserRepository;
use App\Repositories\FirestoreUserRepository;
use App\Services\Admin\AdminFirestoreRepository;
use App\Services\Firebase\FirebaseClientFactory;
use App\Services\Firebase\FirestoreRestApi;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('staff-login', function (Request $request) {
            return Limit::perMinute(10)->by(
                strtolower((string) $request->input('email')).'|'.$request->ip()
            );
        });

        RateLimiter::for('scanner-scan', function (Request $request) {
            $staffId = (string) optional($request->user('staff'))->getAuthIdentifier();
            $stationId = $request->hasSession()
                ? (string) $request->session()->get('staff_station_id', 'no-station')
                : 'no-station';

            return Limit::perMinute(120)->by($staffId.'|'.$stationId.'|'.$request->ip());
        });
    }
}
