<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResendVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:register-api')->post('/register', RegisterController::class);
Route::middleware('throttle:resend-verification')->post('/email/resend-verification', ResendVerificationController::class);

Route::post('/login', function (Request $request) {
    return response()->json([
        'message' => 'Login endpoint should be implemented in portal module.',
    ]);
})->middleware('email.verified.login');
