<?php

use App\Http\Middleware\EnsureEmailVerifiedForLogin;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('register-api', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
RateLimiter::for('resend-verification', fn (Request $request) => Limit::perMinute(3)->by($request->ip().'|'.$request->input('email')));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'email.verified.login' => EnsureEmailVerifiedForLogin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
