<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminPanelService;
use App\Services\Admin\CampaignLinkService;
use App\Services\PublicReportService;
use App\Services\TrafficVisitService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        AdminPanelService $adminPanel,
        CampaignLinkService $campaignLinkService,
        PublicReportService $publicReportService,
        TrafficVisitService $trafficVisitService,
    ): View
    {
        $filters = $request->only(['from', 'to']);

        return view('admin.dashboard', [
            'dashboard' => $adminPanel->dashboardData($filters),
            'filters' => $filters,
            'firestoreAvailable' => $adminPanel->firestoreAvailable(),
            'campaignLinkSummary' => $campaignLinkService->dashboardSummary(),
            'publicReportSummary' => $publicReportService->summary(),
            'trafficVisitSummary' => $trafficVisitService->dashboardSummary(),
        ]);
    }
}
