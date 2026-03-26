<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegistrationService $service): JsonResponse
    {
        try {
            $result = $service->register($request->validated(), (string) $request->ip());

            return response()->json([
                'message' => 'Registration successful. Please check your email for your QR ticket.',
                'status' => 'active',
                'success_url' => route('register.success', ['email' => $result['user']['email']]),
            ], 201);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $throwable) {
            Log::error('Registration failed', ['error' => $throwable->getMessage()]);

            return response()->json([
                'message' => 'Registration failed, please try again.',
            ], 500);
        }
    }
}
