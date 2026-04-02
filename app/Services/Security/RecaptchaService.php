<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecaptchaService
{
    public function shouldVerify(): bool
    {
        return (bool) config('services.recaptcha.enabled');
    }

    public function verify(string $token, ?string $ip = null): bool
    {
        if (! $this->shouldVerify()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        $secretKey = (string) config('services.recaptcha.secret_key');
        if ($secretKey === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post((string) config('services.recaptcha.verify_url'), [
                    'secret' => $secretKey,
                    'response' => $token,
                ]);
        } catch (Throwable) {
            return false;
        }

        if (! $response->successful()) {
            Log::warning('reCAPTCHA verify request was not successful.', [
                'status' => $response->status(),
                'ip' => $ip,
            ]);
            return false;
        }

        $payload = $response->json();
        $success = (bool) data_get($payload, 'success', false);

        if (! $success) {
            Log::warning('reCAPTCHA verification failed.', [
                'error_codes' => data_get($payload, 'error-codes', []),
                'hostname' => data_get($payload, 'hostname'),
                'action' => data_get($payload, 'action'),
                'score' => data_get($payload, 'score'),
                'ip' => $ip,
            ]);
        }

        return $success;
    }
}
