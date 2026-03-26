<?php

use App\Helpers\EmailMasker;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\Auth\AuthenticatedAdminSessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Ticket\TicketPageController;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegistrationService;
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

Route::post('/register', function (RegisterRequest $request, RegistrationService $service) {
    try {
        $result = $service->register($request->validated(), $request->ip());

        return redirect()->route('register.success', ['email' => $result['user']['email']]);
    } catch (InvalidArgumentException $e) {
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

Route::get('/ticket/{ticketId}', TicketPageController::class)
    ->middleware('signed')
    ->name('ticket.show');

$adminPath = config('admin.path', 'admin');

Route::prefix($adminPath)
    ->name('admin.')
    ->group(function () {
        Route::middleware('guest:admin')->group(function () {
            Route::get('/login', function () {
                return redirect()->route('login');
            })->name('login');

            Route::post('/login', [AuthenticatedAdminSessionController::class, 'store'])
                ->middleware('throttle:admin-login')
                ->name('login.store');
        });

        Route::middleware('auth:admin')->group(function () {
            Route::get('/', function () {
                return redirect()->route('admin.dashboard');
            })->name('home');

            Route::post('/logout', [AuthenticatedAdminSessionController::class, 'destroy'])
                ->name('logout');

            Route::get('/dashboard', DashboardController::class)
                ->name('dashboard');

            Route::get('/users', [UserManagementController::class, 'index'])
                ->name('users.index');
            Route::get('/users/{userId}/edit', [UserManagementController::class, 'edit'])
                ->name('users.edit');
            Route::put('/users/{userId}', [UserManagementController::class, 'update'])
                ->name('users.update');
            Route::delete('/users/{userId}', [UserManagementController::class, 'destroy'])
                ->name('users.destroy');
            Route::post('/users/{userId}/qr/reset', [UserManagementController::class, 'resetQr'])
                ->name('users.qr.reset');
            Route::post('/users/{userId}/qr/regenerate', [UserManagementController::class, 'regenerateQr'])
                ->name('users.qr.regenerate');

            Route::get('/attendance', AttendanceController::class)
                ->name('attendance.index');

            Route::get('/logs', AdminActivityLogController::class)
                ->name('logs.index');

            Route::get('/reports', ReportController::class)
                ->name('reports.index');

            Route::get('/exports/{type}/{format}', ExportController::class)
                ->whereIn('type', ['users', 'attendance', 'admin-logs', 'daily-report', 'overall-report'])
                ->whereIn('format', ['csv', 'xlsx'])
                ->name('exports.download');
        });
    });

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
