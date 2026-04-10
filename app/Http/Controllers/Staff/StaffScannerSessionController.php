<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Scanner\ScannerGateService;
use Illuminate\Http\JsonResponse;

class StaffScannerSessionController extends Controller
{
    private const DEVICE_PROFILES = ['laptop', 'android', 'iphone'];

    public function __construct(
        private readonly ScannerGateService $gateService,
    ) {}

    public function show(): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();
        $this->gateService->clearCache();

        $scannerPost = $this->gateService->normalizeSelected(
            (string) session((string) config('scanner.session_post_key', 'staff.scanner_post'))
        );

        if ($scannerPost === '') {
            session()->forget((string) config('scanner.session_post_key', 'staff.scanner_post'));
        }

        $scannerDeviceProfile = $this->normalizeDeviceProfile(
            session((string) config('scanner.session_device_profile_key', 'staff.scanner_device_profile'))
        );

        if ($scannerDeviceProfile === null) {
            session()->forget((string) config('scanner.session_device_profile_key', 'staff.scanner_device_profile'));
        }

        return response()->json([
            'user' => [
                'id' => (string) $admin->getKey(),
                'name' => (string) $admin->name,
                'email' => (string) $admin->email,
                'role' => (string) $admin->role,
            ],
            'scanner_post' => $scannerPost !== '' ? $scannerPost : null,
            'scanner_device_profile' => $scannerDeviceProfile,
        ]);
    }

    public function updatePost(): JsonResponse
    {
        return response()->json([
            'message' => 'Scanner gate is locked after sign-in. Log out and sign in again to use another gate.',
        ], 403);
    }

    private function normalizeDeviceProfile(mixed $value): ?string
    {
        $profile = trim(strtolower((string) $value));

        return in_array($profile, self::DEVICE_PROFILES, true) ? $profile : null;
    }
}
