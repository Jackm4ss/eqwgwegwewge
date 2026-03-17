<?php

namespace App\Providers;

use App\Contracts\UserRepositoryInterface;
use App\Repositories\FirestoreUserRepository;
use App\Services\Firebase\FirebaseClientFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FirebaseClientFactory::class, fn () => new FirebaseClientFactory(config('firebase')));
        $this->app->bind(UserRepositoryInterface::class, FirestoreUserRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
