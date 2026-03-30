<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Scanner\ScannerGateService;
use Illuminate\Http\JsonResponse;

class StaffScannerSessionController extends Controller
{
    public function __construct(
        private readonly ScannerGateService $gateService,
    ) {}

    public function show(): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        $scannerPost = $this->gateService->normalizeSelected(
            (string) session((string) config('scanner.session_post_key', 'staff.scanner_post'))
        );

        if ($scannerPost === '') {
            session()->forget((string) config('scanner.session_post_key', 'staff.scanner_post'));
        }

        return response()->json([
            'user' => [
                'id' => (string) $admin->getKey(),
                'name' => (string) $admin->name,
                'email' => (string) $admin->email,
                'role' => (string) $admin->role,
            ],
            'scanner_post' => $scannerPost !== '' ? $scannerPost : null,
        ]);
    }

    public function updatePost(): JsonResponse
    {
        return response()->json([
            'message' => 'Scanner gate is locked after sign-in. Log out and sign in again to use another gate.',
        ], 403);
    }
}
