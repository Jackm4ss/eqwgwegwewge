<?php

use App\Helpers\EmailMasker;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\RegisterPageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('welcome');
})->name('login');

Route::get('/register', function () {
    return view('welcome');
})->name('register.form');

Route::post('/api/register', \App\Http\Controllers\Api\Auth\RegisterController::class)->name('api.register');

Route::post('/register', function (\App\Http\Requests\Auth\RegisterRequest $request, \App\Services\Auth\RegistrationService $service) {
    try {
        $user = $service->register($request->validated(), $request->ip());
        return redirect()->route('register.success', ['email' => $user['email']]);
    } catch (\InvalidArgumentException $e) {
        return back()->withInput($request->except('password', 'password_confirmation'))->withErrors(['error' => $e->getMessage()]);
    }
})->name('register.submit'); // Renamed to register.submit

Route::get('/register/success', function () {
    $email = request('email');

    abort_if(! $email, 404);

    return view('auth.register-success', [
        'maskedEmail' => EmailMasker::mask($email),
        'email' => $email,
    ]);
})->name('register.success');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::get('/email/verified', [EmailVerificationController::class, 'success'])
    ->name('email.verified');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login successful.',
                'redirect' => '/', // or intended
            ]);
        }

        return redirect()->intended('/');
    }

    if ($request->expectsJson()) {
        return response()->json([
            'message' => 'The provided credentials do not match our records.',
        ], 422);
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.submit');

/*
|--------------------------------------------------------------------------
| Forgot / Reset Password
|--------------------------------------------------------------------------
*/

// Form forgot password
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

// Kirim email reset password
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
    ->middleware('throttle:6,1')
    ->name('password.email');

// Halaman cek email / resend
Route::get('/reset-verify', function () {
    $email = session('reset_email');

    if (! $email) {
        return redirect()->route('password.request');
    }

    return view('reset.reset-verify', [
        'email' => $email,
        'maskedEmail' => EmailMasker::mask($email),
    ]);
})->name('reset.verify');

// Resend email reset password
Route::post('/reset-verify/resend', [ForgotPasswordController::class, 'resendResetLink'])
    ->middleware('throttle:3,1')
    ->name('password.resend');

// Form reset password dari link email
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');

// Submit password baru
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
    ->middleware('throttle:6,1')
    ->name('password.update');

// Halaman sukses reset password
Route::get('/reset-sukses', function () {
    return view('reset.reset-sukses');
})->name('reset.sukses');
