<?php

namespace App\Http\Controllers\Api\Scanner;

use App\Http\Controllers\Controller;
use App\Models\ScannerStation;
use App\Services\Scanner\ScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TodayStatsController extends Controller
{
    public function __invoke(Request $request, ScannerService $scannerService): JsonResponse
    {
        $station = ScannerStation::query()->findOrFail((int) $request->session()->get('staff_station_id'));

        return response()->json($scannerService->todayStats($station));
    }
}
