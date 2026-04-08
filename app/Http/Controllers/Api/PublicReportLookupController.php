<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicReportLookupRequest;
use App\Services\PublicReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublicReportLookupController extends Controller
{
    public function __invoke(PublicReportLookupRequest $request, PublicReportService $publicReportService): JsonResponse
    {
        try {
            return response()->json(
                $publicReportService->lookupForPublic($request->validated())
            );
        } catch (Throwable $throwable) {
            Log::error('Public report lookup failed', [
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'found' => false,
                'message' => 'Unable to check your report right now. Please try again.',
            ], 500);
        }
    }
}
