<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;

class RecaptchaService
{
    public function verify(string $token, ?string $ip = null): bool
    {
        if (! config('services.recaptcha.enabled')) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        $response = Http::asForm()->post(config('services.recaptcha.verify_url'), [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $token,
            'remoteip' => $ip,
        ]);

        return (bool) data_get($response->json(), 'success', false);
    }
}
