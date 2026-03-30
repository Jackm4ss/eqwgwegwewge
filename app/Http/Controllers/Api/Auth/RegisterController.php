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
            $emailSent = (bool) data_get($result, 'delivery.email_sent', false);

            return response()->json([
                'message' => $emailSent
                    ? 'Registration successful. Your QR ticket has been sent to your email.'
                    : 'Registration successful, but we could not send your ticket email right now. Use the direct ticket link below.',
                'status' => $emailSent ? 'ticket_ready' : 'ticket_ready_email_pending',
                'email_sent' => $emailSent,
                'ticket_url' => (string) data_get($result, 'delivery.ticket_url', ''),
                'ticket_qr_url' => (string) data_get($result, 'delivery.ticket_qr_url', ''),
                'ticket_code' => (string) data_get($result, 'ticket.ticket_code', ''),
                'entry_code_display' => (string) data_get($result, 'ticket.entry_code_display', ''),
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
