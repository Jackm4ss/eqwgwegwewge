<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotQrLookupRequest;
use App\Services\Auth\ForgotQrLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ForgotQrLookupController extends Controller
{
    public function __invoke(ForgotQrLookupRequest $request, ForgotQrLookupService $service): JsonResponse
    {
        try {
            return response()->json(
                $service->lookup($request->validated())
            );
        } catch (\Throwable $throwable) {
            Log::error('Forgot QR lookup failed', ['error' => $throwable->getMessage()]);

            return response()->json([
                'found' => false,
                'message' => 'Terjadi gangguan. Silakan coba lagi.',
            ], 500);
        }
    }
}
