<?php

namespace App\Providers;

use App\Contracts\UserRepositoryInterface;
use App\Repositories\FirestoreUserRepository;
use App\Services\Firebase\FirebaseClientFactory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            FirebaseClientFactory::class,
            fn () => new FirebaseClientFactory(config('firebase'))
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            FirestoreUserRepository::class
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
    }
}