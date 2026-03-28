<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ScannerStation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StaffProfilePageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $stationId = $request->session()->get('staff_station_id');
        $station = is_numeric($stationId)
            ? ScannerStation::query()->find((int) $stationId)
            : null;

        return view('staff.profile', [
            'station' => $station,
        ]);
    }
}
