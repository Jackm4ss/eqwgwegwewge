<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicReportRequest;
use App\Services\PublicReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublicReportController extends Controller
{
    public function __invoke(PublicReportRequest $request, PublicReportService $publicReportService): JsonResponse
    {
        $payload = $request->validated();

        try {
            $report = $publicReportService->create(
                $payload,
                (string) $request->ip(),
                (string) $request->userAgent(),
            );
        } catch (Throwable $throwable) {
            Log::error('Public report submission failed', [
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to submit your report right now. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' => 'Your report has been submitted successfully.',
            'reference' => (string) $report->case_id,
        ], 201);
    }
}
