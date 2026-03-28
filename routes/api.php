<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResendVerificationController;
use App\Http\Controllers\Api\Scanner\ScanController;
use App\Http\Controllers\Api\Scanner\TodayStatsController;
use App\Http\Controllers\Api\Staff\CurrentStaffController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:register-api')->post('/register', RegisterController::class);
Route::middleware('throttle:resend-verification')->post('/email/resend-verification', ResendVerificationController::class);

Route::post('/login', function (Request $request) {
    return response()->json([
        'message' => 'Login endpoint should be implemented in portal module.',
    ]);
})->middleware('email.verified.login');

Route::middleware(['web', 'auth:staff'])->group(function () {
    Route::get('/staff/me', CurrentStaffController::class);

    Route::middleware('staff.station.selected')->group(function () {
        Route::post('/scanner/scan', ScanController::class)
            ->middleware('throttle:scanner-scan');
        Route::get('/scanner/stats/today', TodayStatsController::class);
    });
});
