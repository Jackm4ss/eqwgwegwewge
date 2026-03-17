<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\JsonResponse;

class ResendVerificationController extends Controller
{
    public function __invoke(ResendVerificationRequest $request, RegistrationService $service): JsonResponse
    {
        $service->resendVerification($request->validated('email'));

        return response()->json([
            'message' => 'If your account exists and not verified, a verification email has been sent.',
        ]);
    }
}
