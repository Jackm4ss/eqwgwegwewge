<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StaffManualConfirmRequest;
use App\Http\Requests\Staff\StaffManualLookupRequest;
use App\Http\Requests\Staff\StaffScanRequest;
use App\Models\Admin;
use App\Services\Scanner\ScannerGateService;
use App\Services\Staff\StaffScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StaffScannerController extends Controller
{
    public function __construct(
        private readonly StaffScannerService $scanner,
        private readonly ScannerGateService $gateService,
    ) {}

    public function scan(StaffScanRequest $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return response()->json(
            $this->scanner->scan(
                $admin,
                $this->scannerPostFromSession(),
                (string) $request->validated('payload'),
                $request->ip(),
            )
        );
    }

    public function manualLookup(StaffManualLookupRequest $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return response()->json(
            $this->scanner->manualLookup(
                $admin,
                $this->scannerPostFromSession(),
                (string) $request->validated('entry_code'),
            )
        );
    }

    public function manualConfirm(StaffManualConfirmRequest $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        try {
            $result = $this->scanner->manualConfirm(
                $admin,
                $this->scannerPostFromSession(),
                (string) $request->validated('resolution_token'),
                $request->ip(),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($result);
    }

    public function history(): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return response()->json(
            $this->scanner->history($admin, $this->scannerPostFromSession())
        );
    }

    public function stats(): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return response()->json(
            $this->scanner->stats($admin, $this->scannerPostFromSession())
        );
    }

    private function scannerPostFromSession(): string
    {
        $scannerPost = $this->gateService->normalizeSelected(
            (string) session((string) config('scanner.session_post_key', 'staff.scanner_post'))
        );

        if ($scannerPost === '') {
            throw ValidationException::withMessages([
                'scanner_post' => ['Select an active gate before continuing.'],
            ]);
        }

        return $scannerPost;
    }
}
