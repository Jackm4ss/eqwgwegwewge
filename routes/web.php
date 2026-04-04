<?php

use App\Helpers\EmailMasker;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminPresenceController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\ScannerUserController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\Auth\AuthenticatedAdminSessionController;
use App\Http\Controllers\Admin\CampaignLinkController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\PublicReportController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ScannerGateController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\CampaignShortLinkRedirectController;
use App\Http\Controllers\Staff\Auth\AuthenticatedStaffSessionController;
use App\Http\Controllers\Staff\StaffScannerController;
use App\Http\Controllers\Staff\StaffScannerSessionController;
use App\Http\Controllers\Ticket\TicketPageController;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegistrationService;
use App\Support\AppRouting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$isSubdomainMode = AppRouting::isSubdomainMode();

$renderSpa = static function (string $context) {
    return view('welcome', [
        'spaContext' => $context,
        'spaConfig' => AppRouting::spaConfig($context),
        'staffScannerPosts' => config('scanner.posts', ['Gate A']),
        'pwaManifestUrl' => $context === 'staff' ? route('pwa.staff.manifest') : null,
    ]);
};

$renderRegisterSpa = static function () {
    $spaConfig = AppRouting::spaConfig('public');
    $configuredRegisterPath = parse_url(AppRouting::registerUrl(), PHP_URL_PATH);
    $registerPath = AppRouting::normalizePath(
        is_string($configuredRegisterPath) && $configuredRegisterPath !== ''
            ? $configuredRegisterPath
            : '/'
    );

    $spaConfig['paths']['landing'] = [];
    $spaConfig['paths']['register'] = collect([
        '/',
        '/register',
        $registerPath,
    ])->filter()->unique()->values()->all();
    $spaConfig['urls']['registerForm'] = AppRouting::registerUrl();

    return view('welcome', [
        'spaContext' => 'public',
        'spaConfig' => $spaConfig,
        'staffScannerPosts' => config('scanner.posts', ['Gate A']),
        'pwaManifestUrl' => null,
    ]);
};

$renderReportSpa = static function () {
    $spaConfig = AppRouting::spaConfig('public');
    $spaConfig['paths']['landing'] = [];
    $spaConfig['paths']['register'] = [];
    $spaConfig['paths']['forgotQr'] = [];
    $spaConfig['paths']['report'] = ['/', AppRouting::routePath('report.form')];

    return view('welcome', [
        'spaContext' => 'public',
        'spaConfig' => $spaConfig,
        'staffScannerPosts' => config('scanner.posts', ['Gate A']),
        'pwaManifestUrl' => null,
    ]);
};

$redirectAuthenticatedAdmin = static function () {
    if (! Auth::guard('admin')->check()) {
        return null;
    }

    $admin = Auth::guard('admin')->user();

    if (($admin?->role ?? null) === 'scanner') {
        return redirect()->route('staff.home');
    }

    return redirect()->route('admin.dashboard');
};

$adminLoginPage = static function () use ($renderSpa, $redirectAuthenticatedAdmin) {
    return $redirectAuthenticatedAdmin() ?? $renderSpa('admin');
};

$adminHomePage = static function () {
    if (! Auth::guard('admin')->check()) {
        return redirect()->route('login');
    }

    $admin = Auth::guard('admin')->user();

    if (($admin?->role ?? null) === 'scanner') {
        return redirect()->route('staff.home');
    }

    return redirect()->route('admin.dashboard');
};

$staffLoginPage = static function () use ($renderSpa, $redirectAuthenticatedAdmin) {
    return $redirectAuthenticatedAdmin() ?? $renderSpa('staff');
};

$staffHomePage = static function () use ($renderSpa) {
    if (! Auth::guard('admin')->check()) {
        return redirect()->route('staff.login');
    }

    $admin = Auth::guard('admin')->user();

    if (($admin?->role ?? null) === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return $renderSpa('staff');
};

$groupForDomain = static function (?string $domain, callable $routes): void {
    if ($domain !== null && $domain !== '') {
        Route::domain($domain)->group($routes);

        return;
    }

    Route::group([], $routes);
};

Route::get('/pwa/staff-manifest.webmanifest', function () {
    $startUrl = AppRouting::routePath('staff.home');

    return response(
        json_encode([
            'id' => $startUrl,
            'name' => 'Songkran Scanner',
            'short_name' => 'Scanner',
            'description' => 'Install the Songkran Festival 2026 scanner for faster gate access on Android devices.',
            'start_url' => $startUrl,
            'scope' => $startUrl,
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'background_color' => '#0c4a6e',
            'theme_color' => '#0c4a6e',
            'icons' => [
                [
                    'src' => '/pwa/icons/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/pwa/icons/maskable-icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
                [
                    'src' => '/pwa/icons/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/pwa/icons/maskable-icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        200,
        ['Content-Type' => 'application/manifest+json'],
    );
})->name('pwa.staff.manifest');

$publicRoutes = static function () use ($renderSpa, $isSubdomainMode, $adminLoginPage) {
    Route::get('/', static fn () => $renderSpa('public'))
        ->name('landing.home');

    if (! $isSubdomainMode) {
        Route::get('/login', $adminLoginPage)
            ->name('login');
    }

    Route::get('/register', static fn () => $renderSpa('public'))
        ->name('register.form');

    Route::get('/forgot-qr', static fn () => $renderSpa('public'))
        ->name('forgot-qr.form');

    Route::get('/report', static fn () => $renderSpa('public'))
        ->name('report.form');

    Route::post('/register', function (RegisterRequest $request, RegistrationService $service) {
        try {
            $result = $service->register($request->validated(), $request->ip());

            return redirect()->route('register.success', ['email' => data_get($result, 'user.email')]);
        } catch (InvalidArgumentException $e) {
            return back()->withInput($request->except('password', 'password_confirmation'))->withErrors(['error' => $e->getMessage()]);
        }
    })->name('register.submit');

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

    Route::get('/ticket/{ticketId}/download', [TicketPageController::class, 'download'])
        ->middleware('signed')
        ->name('ticket.download');

    Route::get('/ticket/{ticketId}/qr', [TicketPageController::class, 'qr'])
        ->middleware('signed')
        ->name('ticket.qr');

    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->name('password.request');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
        ->middleware('throttle:6,1')
        ->name('password.email');

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

    Route::post('/reset-verify/resend', [ForgotPasswordController::class, 'resendResetLink'])
        ->middleware('throttle:3,1')
        ->name('password.resend');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])
        ->name('password.reset');

    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('/reset-sukses', function () {
        return view('reset.reset-sukses');
    })->name('reset.sukses');
};

$groupForDomain(AppRouting::hostFor('public'), $publicRoutes);

if ($isSubdomainMode) {
    $registerHost = AppRouting::hostFor('register');
    $publicHost = AppRouting::hostFor('public');
    $helpHost = AppRouting::hostFor('help');

    if ($registerHost !== null && $registerHost !== '' && $registerHost !== $publicHost) {
        $groupForDomain($registerHost, static function () use ($renderRegisterSpa) {
            Route::get('/', $renderRegisterSpa);
            Route::get('/register', $renderRegisterSpa);
            Route::get('/forgot-qr', $renderRegisterSpa);
            Route::get('/report', $renderRegisterSpa);
        });
    }

    if ($helpHost !== null && $helpHost !== '' && $helpHost !== $publicHost && $helpHost !== $registerHost) {
        $groupForDomain($helpHost, static function () use ($renderReportSpa) {
            Route::get('/', $renderReportSpa);
            Route::get('/report', $renderReportSpa);
        });
    }
}

if ($isSubdomainMode) {
    $groupForDomain(AppRouting::hostFor('admin'), static function () use ($adminHomePage, $adminLoginPage) {
        Route::get('/', $adminHomePage)
            ->name('admin.home');

        Route::middleware('guest:admin')->group(function () use ($adminLoginPage) {
            Route::get('/login', $adminLoginPage)
                ->name('login');

            Route::post('/login', [AuthenticatedAdminSessionController::class, 'store'])
                ->middleware('throttle:admin-login')
                ->name('admin.login.store');
        });

        Route::name('admin.')
            ->middleware(['auth:admin', 'admin.role:admin'])
            ->group(function () {
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

                Route::get('/gates', [ScannerGateController::class, 'index'])
                    ->name('gates.index');
                Route::post('/gates', [ScannerGateController::class, 'store'])
                    ->name('gates.store');
                Route::put('/gates/{gate}', [ScannerGateController::class, 'update'])
                    ->name('gates.update');
                Route::delete('/gates/{gate}', [ScannerGateController::class, 'destroy'])
                    ->name('gates.destroy');

                Route::get('/campaign-links', [CampaignLinkController::class, 'index'])
                    ->name('campaign-links.index');
                Route::post('/campaign-links', [CampaignLinkController::class, 'store'])
                    ->name('campaign-links.store');
                Route::put('/campaign-links/{campaignLink}', [CampaignLinkController::class, 'update'])
                    ->name('campaign-links.update');
                Route::delete('/campaign-links/{campaignLink}', [CampaignLinkController::class, 'destroy'])
                    ->name('campaign-links.destroy');

                Route::get('/logs', AdminActivityLogController::class)
                    ->name('logs.index');

                Route::get('/admin-users', [AdminUserController::class, 'index'])
                    ->name('admin-users.index');
                Route::get('/admin-users/create', [AdminUserController::class, 'create'])
                    ->name('admin-users.create');
                Route::post('/admin-users', [AdminUserController::class, 'store'])
                    ->name('admin-users.store');
                Route::get('/admin-users/{adminUser}/edit', [AdminUserController::class, 'edit'])
                    ->name('admin-users.edit');
                Route::put('/admin-users/{adminUser}', [AdminUserController::class, 'update'])
                    ->name('admin-users.update');
                Route::delete('/admin-users/{adminUser}', [AdminUserController::class, 'destroy'])
                    ->name('admin-users.destroy');
                Route::get('/scanner-users', [ScannerUserController::class, 'index'])
                    ->name('scanner-users.index');
                Route::get('/scanner-users/create', [ScannerUserController::class, 'create'])
                    ->name('scanner-users.create');
                Route::post('/scanner-users', [ScannerUserController::class, 'store'])
                    ->name('scanner-users.store');
                Route::get('/scanner-users/{scannerUser}/edit', [ScannerUserController::class, 'edit'])
                    ->name('scanner-users.edit');
                Route::put('/scanner-users/{scannerUser}', [ScannerUserController::class, 'update'])
                    ->name('scanner-users.update');
                Route::delete('/scanner-users/{scannerUser}', [ScannerUserController::class, 'destroy'])
                    ->name('scanner-users.destroy');
                Route::post('/presence/heartbeat', [AdminPresenceController::class, 'heartbeat'])
                    ->name('presence.heartbeat');
                Route::get('/presence/statuses', [AdminPresenceController::class, 'statuses'])
                    ->name('presence.statuses');

                Route::get('/reports', ReportController::class)
                    ->name('reports.index');
                Route::get('/public-reports', [PublicReportController::class, 'index'])
                    ->name('public-reports.index');
                Route::put('/public-reports/{publicReport}', [PublicReportController::class, 'update'])
                    ->name('public-reports.update');
                Route::delete('/public-reports/{publicReport}', [PublicReportController::class, 'destroy'])
                    ->name('public-reports.destroy');

                Route::get('/exports/{type}/{format}', ExportController::class)
                    ->whereIn('type', ['users', 'attendance', 'admin-logs', 'daily-report', 'overall-report', 'public-reports'])
                    ->whereIn('format', ['csv', 'xlsx'])
                    ->name('exports.download');
            });
    });
} else {
    $adminPath = AppRouting::adminPath();

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

            Route::middleware(['auth:admin', 'admin.role:admin'])->group(function () {
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

                Route::get('/gates', [ScannerGateController::class, 'index'])
                    ->name('gates.index');
                Route::post('/gates', [ScannerGateController::class, 'store'])
                    ->name('gates.store');
                Route::put('/gates/{gate}', [ScannerGateController::class, 'update'])
                    ->name('gates.update');
                Route::delete('/gates/{gate}', [ScannerGateController::class, 'destroy'])
                    ->name('gates.destroy');

                Route::get('/campaign-links', [CampaignLinkController::class, 'index'])
                    ->name('campaign-links.index');
                Route::post('/campaign-links', [CampaignLinkController::class, 'store'])
                    ->name('campaign-links.store');
                Route::put('/campaign-links/{campaignLink}', [CampaignLinkController::class, 'update'])
                    ->name('campaign-links.update');
                Route::delete('/campaign-links/{campaignLink}', [CampaignLinkController::class, 'destroy'])
                    ->name('campaign-links.destroy');

                Route::get('/logs', AdminActivityLogController::class)
                    ->name('logs.index');

                Route::get('/admin-users', [AdminUserController::class, 'index'])
                    ->name('admin-users.index');
                Route::get('/admin-users/create', [AdminUserController::class, 'create'])
                    ->name('admin-users.create');
                Route::post('/admin-users', [AdminUserController::class, 'store'])
                    ->name('admin-users.store');
                Route::get('/admin-users/{adminUser}/edit', [AdminUserController::class, 'edit'])
                    ->name('admin-users.edit');
                Route::put('/admin-users/{adminUser}', [AdminUserController::class, 'update'])
                    ->name('admin-users.update');
                Route::delete('/admin-users/{adminUser}', [AdminUserController::class, 'destroy'])
                    ->name('admin-users.destroy');
                Route::get('/scanner-users', [ScannerUserController::class, 'index'])
                    ->name('scanner-users.index');
                Route::get('/scanner-users/create', [ScannerUserController::class, 'create'])
                    ->name('scanner-users.create');
                Route::post('/scanner-users', [ScannerUserController::class, 'store'])
                    ->name('scanner-users.store');
                Route::get('/scanner-users/{scannerUser}/edit', [ScannerUserController::class, 'edit'])
                    ->name('scanner-users.edit');
                Route::put('/scanner-users/{scannerUser}', [ScannerUserController::class, 'update'])
                    ->name('scanner-users.update');
                Route::delete('/scanner-users/{scannerUser}', [ScannerUserController::class, 'destroy'])
                    ->name('scanner-users.destroy');
                Route::post('/presence/heartbeat', [AdminPresenceController::class, 'heartbeat'])
                    ->name('presence.heartbeat');
                Route::get('/presence/statuses', [AdminPresenceController::class, 'statuses'])
                    ->name('presence.statuses');

                Route::get('/reports', ReportController::class)
                    ->name('reports.index');
                Route::get('/public-reports', [PublicReportController::class, 'index'])
                    ->name('public-reports.index');
                Route::put('/public-reports/{publicReport}', [PublicReportController::class, 'update'])
                    ->name('public-reports.update');
                Route::delete('/public-reports/{publicReport}', [PublicReportController::class, 'destroy'])
                    ->name('public-reports.destroy');

                Route::get('/exports/{type}/{format}', ExportController::class)
                    ->whereIn('type', ['users', 'attendance', 'admin-logs', 'daily-report', 'overall-report', 'public-reports'])
                    ->whereIn('format', ['csv', 'xlsx'])
                    ->name('exports.download');
            });
        });
}

if ($isSubdomainMode) {
    $groupForDomain(AppRouting::hostFor('staff'), static function () use ($staffLoginPage, $staffHomePage) {
        Route::get('/login', $staffLoginPage)
            ->name('staff.login');

        Route::get('/', $staffHomePage)
            ->name('staff.home');

        Route::middleware('guest:admin')->group(function () {
            Route::post('/login', [AuthenticatedStaffSessionController::class, 'store'])
                ->middleware('throttle:admin-login')
                ->name('staff.login.store');
        });

        Route::name('staff.')
            ->middleware(['auth:admin', 'admin.role:scanner'])
            ->group(function () {
                Route::post('/logout', [AuthenticatedStaffSessionController::class, 'destroy'])
                    ->name('logout');

                Route::get('/session', [StaffScannerSessionController::class, 'show'])
                    ->name('session.show');

                Route::post('/session/scanner-post', [StaffScannerSessionController::class, 'updatePost'])
                    ->name('session.scanner-post');

                Route::post('/scan', [StaffScannerController::class, 'scan'])
                    ->name('scan');

                Route::post('/manual-lookup', [StaffScannerController::class, 'manualLookup'])
                    ->name('manual-lookup');

                Route::post('/manual-confirm', [StaffScannerController::class, 'manualConfirm'])
                    ->name('manual-confirm');

                Route::get('/dashboard', [StaffScannerController::class, 'dashboard'])
                    ->name('dashboard');

                Route::get('/history', [StaffScannerController::class, 'history'])
                    ->name('history');

                Route::get('/stats', [StaffScannerController::class, 'stats'])
                    ->name('stats');
            });
    });
} else {
    $staffPath = AppRouting::staffPath();

    Route::get('/'.$staffPath.'/login', $staffLoginPage)
        ->name('staff.login');

    Route::get('/'.$staffPath, $staffHomePage)
        ->name('staff.home');

    Route::prefix($staffPath)
        ->name('staff.')
        ->group(function () {
            Route::middleware('guest:admin')->group(function () {
                Route::post('/login', [AuthenticatedStaffSessionController::class, 'store'])
                    ->middleware('throttle:admin-login')
                    ->name('login.store');
            });

            Route::middleware(['auth:admin', 'admin.role:scanner'])->group(function () {
                Route::post('/logout', [AuthenticatedStaffSessionController::class, 'destroy'])
                    ->name('logout');

                Route::get('/session', [StaffScannerSessionController::class, 'show'])
                    ->name('session.show');

                Route::post('/session/scanner-post', [StaffScannerSessionController::class, 'updatePost'])
                    ->name('session.scanner-post');

                Route::post('/scan', [StaffScannerController::class, 'scan'])
                    ->name('scan');

                Route::post('/manual-lookup', [StaffScannerController::class, 'manualLookup'])
                    ->name('manual-lookup');

                Route::post('/manual-confirm', [StaffScannerController::class, 'manualConfirm'])
                    ->name('manual-confirm');

                Route::get('/dashboard', [StaffScannerController::class, 'dashboard'])
                    ->name('dashboard');

                Route::get('/history', [StaffScannerController::class, 'history'])
                    ->name('history');

                Route::get('/stats', [StaffScannerController::class, 'stats'])
                    ->name('stats');
            });
        });
}

$groupForDomain(AppRouting::hostFor('public'), static function () {
    Route::get('/{slug}', CampaignShortLinkRedirectController::class)
        ->name('campaign-links.redirect');
});
