<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrafficVisitRequest;
use App\Services\TrafficVisitService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class TrafficVisitController extends Controller
{
    public function __invoke(StoreTrafficVisitRequest $request, TrafficVisitService $trafficVisitService): Response
    {
        try {
            $trafficVisitService->recordVisit(
                $request->validated(),
                (string) $request->ip(),
                (string) $request->userAgent(),
            );
        } catch (Throwable $throwable) {
            Log::warning('Traffic visit tracking failed', [
                'error' => $throwable->getMessage(),
            ]);
        }

        return response()->noContent();
    }
}
