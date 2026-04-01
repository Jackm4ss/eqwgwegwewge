<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAuditLogger;
use App\Services\Admin\AdminExportService;
use App\Services\Admin\AdminPanelService;
use App\Services\PublicReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends Controller
{
    public function __invoke(
        Request $request,
        string $type,
        string $format,
        AdminPanelService $adminPanel,
        PublicReportService $publicReportService,
        AdminExportService $exportService,
        AdminAuditLogger $auditLogger,
    ): Response {
        $filters = $request->only(['q', 'from', 'to', 'report_type']);
        $rows = $type === 'public-reports'
            ? $publicReportService->exportRows($filters)
            : $adminPanel->exportRows($type, $filters);
        $format = strtolower($format);
        $filename = sprintf(
            '%s-%s.%s',
            $type,
            now()->format('Ymd-His'),
            $format === 'xlsx' ? 'xlsx' : 'csv',
        );

        $auditLogger->log(
            auth('admin')->user(),
            'export',
            'report',
            $type,
            [
                'format' => $format,
                'rows' => count($rows),
                'filters' => $filters,
            ],
            $request->ip(),
        );

        return $format === 'xlsx'
            ? $exportService->xlsxDownload($filename, $rows)
            : $exportService->csvDownload($filename, $rows);
    }
}
