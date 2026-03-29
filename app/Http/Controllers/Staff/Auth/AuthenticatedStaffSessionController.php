<?php

namespace App\Http\Controllers\Staff\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StaffLoginRequest;
use App\Models\Admin;
use App\Services\Admin\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedStaffSessionController extends Controller
{
    public function __construct(
        private readonly AdminAuditLogger $auditLogger,
    ) {}

    public function store(StaffLoginRequest $request): JsonResponse|RedirectResponse
    {
        $credentials = [
            'email' => (string) $request->input('email'),
            'password' => (string) $request->input('password'),
            'is_active' => true,
            'role' => 'scanner',
        ];

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            return $this->failedLoginResponse($request);
        }

        $request->session()->regenerate();
        $scannerPost = (string) $request->validated('scanner_post');
        $request->session()->put((string) config('scanner.session_post_key', 'staff.scanner_post'), $scannerPost);

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();
        $admin->forceFill(['last_login_at' => now()])->save();

        $this->auditLogger->log(
            $admin,
            'staff_login',
            'staff',
            (string) $admin->getKey(),
            [
                'remember' => $request->boolean('remember'),
                'scanner_post' => $scannerPost,
            ],
            $request->ip(),
        );

        $redirect = url('/'.trim((string) config('scanner.path', 'staff'), '/'));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login successful.',
                'redirect' => $redirect,
                'scanner_post' => $scannerPost,
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
            'staff_logout',
            'staff',
            $admin ? (string) $admin->getKey() : null,
            [],
            $request->ip(),
        );

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirect = url('/'.trim((string) config('scanner.path', 'staff'), '/').'/login');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged out successfully.',
                'redirect' => $redirect,
            ]);
        }

        return redirect()->to($redirect);
    }

    private function failedLoginResponse(StaffLoginRequest $request): JsonResponse|RedirectResponse
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
