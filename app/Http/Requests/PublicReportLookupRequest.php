<?php

namespace App\Http\Requests;

use App\Services\Security\RecaptchaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PublicReportLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9-]+$/'],
            'recaptcha_token' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $recaptcha = app(RecaptchaService::class);

            if (! $recaptcha->shouldVerify()) {
                return;
            }

            $recaptchaToken = trim((string) $this->input('recaptcha_token'));

            if ($recaptchaToken === '') {
                $validator->errors()->add('recaptcha_token', 'Please complete the reCAPTCHA verification.');

                return;
            }

            $isVerified = $recaptcha->verify($recaptchaToken, $this->ip());

            if (! $isVerified) {
                $validator->errors()->add('recaptcha_token', 'reCAPTCHA verification failed. Please try again.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $reference = strtoupper((string) preg_replace('/\s+/', '', trim((string) $this->input('reference'))));

        $this->merge([
            'reference' => $reference,
            'recaptcha_token' => trim((string) $this->input('recaptcha_token')),
        ]);
    }
}
