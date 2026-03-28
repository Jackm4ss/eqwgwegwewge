<?php

namespace App\Http\Middleware;

use App\Models\ScannerStation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffStationSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        $stationId = $request->session()->get('staff_station_id');

        if (! is_numeric($stationId)) {
            return $this->missingStationResponse($request);
        }

        $station = ScannerStation::query()
            ->active()
            ->find((int) $stationId);

        if ($station === null) {
            $request->session()->forget('staff_station_id');

            return $this->missingStationResponse($request);
        }

        return $next($request);
    }

    private function missingStationResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Scanner station must be selected before scanning.',
            ], 428);
        }

        return redirect()->route('staff.station.create');
    }
}
