<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPanelService;
use App\Services\PublicReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __invoke(Request $request, AdminPanelService $adminPanel, PublicReportService $publicReportService): View
    {
        $filters = $request->only(['q', 'from', 'to', 'report_type', 'per_page']);

        return view('admin.reports.index', [
            'reports' => $adminPanel->reports($filters),
            'filters' => $filters,
            'firestoreAvailable' => $adminPanel->firestoreAvailable(),
            'publicReports' => $publicReportService->paginateForAdmin($filters),
            'publicReportSummary' => $publicReportService->summary($filters),
            'publicReportTypeOptions' => $publicReportService->reportTypeOptions(),
            'publicReportTypeLabel' => fn (string $value): string => $publicReportService->reportTypeLabel($value),
        ]);
    }
}
