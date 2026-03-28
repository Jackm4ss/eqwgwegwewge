<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPanelService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AdminPanelService $adminPanel): View
    {
        $filters = $adminPanel->normalizedScanLogFilters(
            $request->only(['from', 'to'])
        );

        return view('admin.dashboard', [
            'dashboard' => $adminPanel->dashboardData($filters),
            'filters' => $filters,
            'firestoreAvailable' => $adminPanel->firestoreAvailable(),
        ]);
    }
}
