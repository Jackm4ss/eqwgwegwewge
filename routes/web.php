<?php

use App\Helpers\EmailMasker;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisterPageController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('register.form'));

Route::get('/register', RegisterPageController::class)->name('register.form');

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

Route::get('/login', function () {
    return view('login');
})->name('login');

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
    ->name('password.email');

// Halaman cek email / resend
Route::get('/reset-verify', function () {
    $email = session('reset_email');

    aif (! $email) {
    return redirect()->route('password.request');
}

    return view('reset.reset-verify', [
        'email' => $email,
        'maskedEmail' => EmailMasker::mask($email),
    ]);
})->name('reset.verify');

// Resend email reset password
Route::post('/reset-verify/resend', [ForgotPasswordController::class, 'resendResetLink'])
    ->name('password.resend');

// Form reset password dari link email
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');

// Submit password baru
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
    ->name('password.update');

// Halaman sukses reset password
Route::get('/reset-sukses', function () {
    return view('reset.reset-sukses');
})->name('reset.sukses');