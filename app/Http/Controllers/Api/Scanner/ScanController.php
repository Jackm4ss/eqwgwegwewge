<?php

namespace App\Http\Controllers\Api\Scanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scanner\ScanRequest;
use App\Models\ScannerStation;
use App\Models\StaffUser;
use App\Services\Scanner\ScannerService;
use Illuminate\Http\JsonResponse;

class ScanController extends Controller
{
    public function __invoke(ScanRequest $request, ScannerService $scannerService): JsonResponse
    {
        /** @var StaffUser $staff */
        $staff = $request->user('staff');
        $station = ScannerStation::query()->findOrFail((int) $request->session()->get('staff_station_id'));
        $result = $scannerService->scan(
            (string) $request->validated('qr_payload'),
            $staff,
            $station,
            $request->ip(),
        );

        return response()->json($result);
    }
}
