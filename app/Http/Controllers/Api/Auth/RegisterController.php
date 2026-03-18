<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegistrationService $service): JsonResponse
    {
        try {
            $user = $service->register($request->validated(), (string) $request->ip());

            return response()->json([
                'message' => 'Registration successful. Verification email has been sent.',
                'redirect' => route('register.success', [
                    'email' => $user['email'],
                ]),
            ]);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $throwable) {
            Log::error('Registration failed', ['error' => $throwable->getMessage()]);

            return response()->json([
                'message' => 'Registration failed, please try again.',
            ], 500);
        }
    }
}