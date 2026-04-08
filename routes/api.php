<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ForgotQrLookupController;
use App\Http\Controllers\Api\Auth\ResendVerificationController;
use App\Http\Controllers\Api\PublicReportLookupController;
use App\Http\Controllers\Api\PublicReportController;
use App\Http\Controllers\Api\TrafficVisitController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:register-api')->post('/register', RegisterController::class);
Route::middleware('throttle:forgot-qr-lookup')->post('/forgot-qr/lookup', ForgotQrLookupController::class);
Route::middleware('throttle:resend-verification')->post('/email/resend-verification', ResendVerificationController::class);
Route::middleware('throttle:public-report-submit')->post('/report', PublicReportController::class);
Route::middleware('throttle:public-report-lookup')->post('/report/lookup', PublicReportLookupController::class);
Route::middleware('throttle:traffic-visit')->post('/traffic/visit', TrafficVisitController::class);

Route::post('/login', function (Request $request) {
    return response()->json([
        'message' => 'Login endpoint should be implemented in portal module.',
    ]);
})->middleware('email.verified.login');
