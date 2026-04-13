<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDashboardSnapshotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        AdminDashboardSnapshotService $dashboardSnapshot,
    ): View
    {
        $filters = $request->only(['from', 'to']);
        $showAttendanceAnalytics = (bool) config('admin.dashboard.attendance_analytics_enabled', false);
        $pageData = $dashboardSnapshot->pageData($filters, $showAttendanceAnalytics);

        return view('admin.dashboard', [
            'filters' => $filters,
            'showAttendanceAnalytics' => $showAttendanceAnalytics,
        ] + $pageData);
    }
}
