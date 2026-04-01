<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicReportRequest;
use App\Mail\PublicReportReceiptMail;
use App\Mail\PublicReportTeamMail;
use App\Services\PublicReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PublicReportController extends Controller
{
    public function __invoke(PublicReportRequest $request, PublicReportService $publicReportService): JsonResponse
    {
        $payload = $request->validated();
        $recipient = (string) config('report.notification_email', 'rs@rsgr.net');

        try {
            $report = $publicReportService->create(
                $payload,
                (string) $request->ip(),
                (string) $request->userAgent(),
            );

            Mail::to($recipient)->send(new PublicReportTeamMail(
                report: $report,
                reportTypeLabel: $publicReportService->reportTypeLabel((string) $report->report_type),
            ));

            if (filled($report->email)) {
                Mail::to((string) $report->email)->send(new PublicReportReceiptMail(
                    report: $report,
                    reportTypeLabel: $publicReportService->reportTypeLabel((string) $report->report_type),
                ));
            }

            return response()->json([
                'message' => 'Your report has been submitted successfully.',
                'reference' => (string) $report->case_id,
                'recipient' => $recipient,
            ], 201);
        } catch (\Throwable $throwable) {
            Log::error('Public report submission failed', [
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to submit your report right now. Please try again.',
            ], 500);
        }
    }
}
