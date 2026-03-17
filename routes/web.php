<?php

use App\Helpers\EmailMasker;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisterPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('register.form'));
Route::get('/register', RegisterPageController::class)->name('register.form');

Route::get('/register/success', function () {
    $email = session('registered_email');

    abort_if(! $email, 404);

    return view('auth.register-success', [
        'maskedEmail' => EmailMasker::mask($email),
        'email' => $email,
    ]);
})->name('register.success');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::get('/email/verified', [EmailVerificationController::class, 'success'])->name('email.verified');

Route::get('/login', fn () => response()->json([
    'message' => 'Integrate this endpoint with portal.songkremfestival.my login page.',
]))->name('login');
