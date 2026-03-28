<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ScannerStation;
use App\Services\Scanner\ScannerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StaffStatsPageController extends Controller
{
    public function __invoke(Request $request, ScannerService $scannerService): View
    {
        $station = ScannerStation::query()->findOrFail((int) $request->session()->get('staff_station_id'));

        return view('staff.stats', [
            'station' => $station,
            'stats' => $scannerService->todayStats($station),
        ]);
    }
}
