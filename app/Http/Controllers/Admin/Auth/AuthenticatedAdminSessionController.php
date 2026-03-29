<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\Admin;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedAdminSessionController extends Controller
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function store(AdminLoginRequest $request): JsonResponse|RedirectResponse
    {
        $credentials = [
            'email' => (string) $request->input('email'),
            'password' => (string) $request->input('password'),
            'is_active' => true,
            'role' => 'admin',
        ];

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            return $this->failedLoginResponse($request);
        }

        $request->session()->regenerate();

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $admin->forceFill(['last_login_at' => now()])->save();

        $this->auditLogger->log(
            $admin,
            'login',
            'admin',
            (string) $admin->getKey(),
            ['remember' => $request->boolean('remember')],
            $request->ip(),
        );

        $redirect = route('admin.dashboard');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login successful.',
                'redirect' => $redirect,
            ]);
        }

        return redirect()->intended($redirect);
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        /** @var Admin|null $admin */
        $admin = Auth::guard('admin')->user();

        $this->auditLogger->log(
            $admin,
            'logout',
            'admin',
            $admin ? (string) $admin->getKey() : null,
            [],
            $request->ip(),
        );

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged out successfully.',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login');
    }

    private function failedLoginResponse(AdminLoginRequest $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'The provided credentials do not match our records.',
            ], 422);
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }
}
