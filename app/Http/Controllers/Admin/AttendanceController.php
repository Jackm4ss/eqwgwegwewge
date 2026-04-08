<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPanelService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __invoke(Request $request, AdminPanelService $adminPanel): View
    {
        $filters = $request->only([
            'q',
            'country',
            'identity_type',
            'attendance_status',
            'scan_result',
            'scanner_post',
            'from',
            'to',
            'page',
            'per_page',
        ]);
        $page = $adminPanel->attendanceManagementPage($filters);

        return view('admin.attendance.index', [
            'rows' => $page['rows'],
            'overview' => $page['overview'] ?? [],
            'filterOptions' => $page['filter_options'] ?? [],
            'syncStatus' => $page['sync_status'] ?? null,
            'filters' => $filters,
            'firestoreAvailable' => $adminPanel->firestoreAvailable(),
        ]);
    }
}
