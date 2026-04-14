<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FoundItemIndexRequest;
use App\Services\LostFoundItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class FoundItemController extends Controller
{
    public function __invoke(FoundItemIndexRequest $request, LostFoundItemService $lostFoundItemService): JsonResponse
    {
        try {
            return response()->json(
                $lostFoundItemService->publicList($request->validated())
            );
        } catch (Throwable $throwable) {
            Log::error('Found items lookup failed', [
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to load found items right now. Please try again.',
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 12,
                    'total' => 0,
                ],
            ], 500);
        }
    }
}
