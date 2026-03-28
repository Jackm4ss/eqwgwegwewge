<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ScannerStation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StationSelectionController extends Controller
{
    public function create(Request $request): View
    {
        return view('staff.station-select', [
            'stations' => ScannerStation::query()
                ->active()
                ->orderBy('gate_name')
                ->orderBy('scanner_name')
                ->get(),
            'selectedStationId' => $request->session()->get('staff_station_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'station_id' => ['required', 'integer', 'exists:scanner_stations,id'],
        ]);

        $station = ScannerStation::query()
            ->active()
            ->findOrFail((int) $validated['station_id']);

        $request->session()->put('staff_station_id', $station->getKey());

        return redirect()->route('staff.scanner');
    }
}
