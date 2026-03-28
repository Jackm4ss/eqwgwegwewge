<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Models\ScannerStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrentStaffController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $staff = $request->user('staff');
        $stationId = $request->session()->get('staff_station_id');
        $station = is_numeric($stationId)
            ? ScannerStation::query()->find((int) $stationId)
            : null;

        return response()->json([
            'staff' => $staff ? [
                'id' => (string) $staff->getKey(),
                'name' => $staff->name,
                'email' => $staff->email,
                'role' => $staff->role,
            ] : null,
            'station' => $station ? [
                'id' => (int) $station->getKey(),
                'station_id' => $station->station_id,
                'scanner_id' => $station->scanner_id,
                'scanner_name' => $station->scanner_name,
                'gate_id' => $station->gate_id,
                'gate_name' => $station->gate_name,
            ] : null,
        ]);
    }
}
