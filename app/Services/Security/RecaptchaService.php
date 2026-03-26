<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Throwable;

class RecaptchaService
{
    public function verify(
        string $token,
        ?string $ip = null,
        ?string $expectedAction = null,
        ?float $minimumScore = null,
    ): bool
    {
        if (! config('services.recaptcha.enabled')) {
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
                    'remoteip' => $ip,
                ]);
        } catch (Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $payload = $response->json();

        if (! (bool) data_get($payload, 'success', false)) {
            return false;
        }

        if ($expectedAction !== null && $expectedAction !== '') {
            if ((string) data_get($payload, 'action', '') !== $expectedAction) {
                return false;
            }
        }

        if ($minimumScore !== null) {
            $score = data_get($payload, 'score');

            if (! is_numeric($score) || (float) $score < $minimumScore) {
                return false;
            }
        }

        return true;
    }
}
