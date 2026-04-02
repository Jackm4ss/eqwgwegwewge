<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePublicReportRequest;
use App\Models\PublicReport;
use App\Services\Admin\AdminAuditLogger;
use App\Services\PublicReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicReportController extends Controller
{
    public function index(Request $request, PublicReportService $publicReportService): View
    {
        $filters = $request->only(['q', 'from', 'to', 'report_type', 'action_status', 'per_page']);

        return view('admin.public-reports.index', [
            'filters' => $filters,
            'publicReports' => $publicReportService->paginateForAdmin($filters),
            'publicReportSummary' => $publicReportService->summary($filters),
            'publicReportTypeOptions' => $publicReportService->reportTypeOptions(),
            'publicReportTypeLabel' => fn (string $value): string => $publicReportService->reportTypeLabel($value),
            'publicReportStatusOptions' => $publicReportService->actionStatusOptions(),
            'publicReportStatusLabel' => fn (string $value): string => $publicReportService->actionStatusLabel($value),
            'publicFormReference' => $publicReportService->formReference(),
        ]);
    }

    public function update(
        UpdatePublicReportRequest $request,
        PublicReport $publicReport,
        PublicReportService $publicReportService,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $before = [
            'action_status' => $publicReport->action_status,
            'admin_note' => $publicReport->admin_note,
        ];

        $updatedReport = $publicReportService->updateAdminReview($publicReport, $request->validated());

        $auditLogger->log(
            auth('admin')->user(),
            'public_report_review_update',
            'public_report',
            (string) $updatedReport->getKey(),
            [
                'case_id' => $updatedReport->case_id,
                'before' => $before,
                'after' => [
                    'action_status' => $updatedReport->action_status,
                    'admin_note' => $updatedReport->admin_note,
                ],
            ],
            $request->ip(),
        );

        return back()->with('status', "Public report {$updatedReport->case_id} updated successfully.");
    }

    public function destroy(
        Request $request,
        PublicReport $publicReport,
        PublicReportService $publicReportService,
        AdminAuditLogger $auditLogger,
    ): RedirectResponse {
        $deletedReport = $publicReportService->deleteForAdmin($publicReport);

        $auditLogger->log(
            auth('admin')->user(),
            'public_report_delete',
            'public_report',
            (string) $deletedReport['id'],
            [
                'case_id' => $deletedReport['case_id'],
                'report_type' => $deletedReport['report_type'],
                'action_status' => $deletedReport['action_status'],
                'name' => $deletedReport['name'],
                'email' => $deletedReport['email'],
                'phone' => $deletedReport['phone'],
            ],
            $request->ip(),
        );

        $fallbackUrl = route('admin.public-reports.index');
        $redirectUrl = $request->headers->get('referer') ?: $fallbackUrl;

        return redirect($redirectUrl)
            ->with('status', "Public report {$deletedReport['case_id']} was deleted successfully.");
    }
}
