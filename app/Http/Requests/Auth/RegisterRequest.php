<?php

namespace App\Http\Requests\Auth;

use App\Services\Security\RecaptchaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'identity_type' => ['required', 'string', Rule::in(['national_id', 'passport'])],
            'identity_number' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:120'],
            'phone_country_code' => ['nullable', 'string', 'regex:/^\+\d{1,4}$/'],
            'phone_national_number' => ['nullable', 'string', 'regex:/^\d{4,20}$/'],
            'phone_number' => ['required', 'string', 'max:30', 'regex:/^\+\d{6,20}$/'],
            'country' => ['required', 'string', 'max:80'],
            'recaptcha_token' => ['nullable', 'string'],
            'agreeTerms' => ['accepted'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $country = strtoupper(trim((string) $this->input('country')));
            $identityType = strtolower(trim((string) $this->input('identity_type')));

            if ($country !== '' && $country !== 'MY' && $identityType === 'national_id') {
                $validator->errors()->add(
                    'identity_type',
                    'Untuk pendaftar luar Malaysia, gunakan Passport sebagai identitas utama.'
                );
            }

            if (! config('services.recaptcha.enabled')) {
                return;
            }

            $recaptchaToken = trim((string) $this->input('recaptcha_token'));

            if ($recaptchaToken === '') {
                $validator->errors()->add(
                    'recaptcha_token',
                    'Mohon selesaikan verifikasi reCAPTCHA.'
                );

                return;
            }

            $expectedAction = trim((string) config('services.recaptcha.expected_action', 'register'));
            $minimumScore = config('services.recaptcha.minimum_score');

            $isVerified = app(RecaptchaService::class)->verify(
                $recaptchaToken,
                $this->ip(),
                $expectedAction !== '' ? $expectedAction : null,
                is_numeric($minimumScore) ? (float) $minimumScore : null,
            );

            if (! $isVerified) {
                $validator->errors()->add(
                    'recaptcha_token',
                    'Verifikasi reCAPTCHA gagal. Silakan coba lagi.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $phoneCountryCode = $this->normalizePhoneCountryCode((string) $this->input('phone_country_code'));
        $phoneNationalNumber = $this->normalizePhoneNationalNumber((string) $this->input('phone_national_number'));
        $phoneNumber = trim((string) $this->input('phone_number'));

        if ($phoneCountryCode !== '' && $phoneNationalNumber !== '') {
            $phoneNumber = $this->combinePhoneNumber($phoneCountryCode, $phoneNationalNumber);
        } elseif ($phoneNumber !== '') {
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
        }

        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'full_name' => trim((string) preg_replace('/\s+/u', ' ', (string) $this->input('full_name'))),
            'identity_type' => strtolower(trim((string) $this->input('identity_type'))),
            'identity_number' => strtoupper(trim((string) $this->input('identity_number'))),
            'phone_country_code' => $phoneCountryCode !== '' ? $phoneCountryCode : null,
            'phone_national_number' => $phoneNationalNumber !== '' ? $phoneNationalNumber : null,
            'phone_number' => $phoneNumber,
            'country' => strtoupper(trim((string) $this->input('country'))),
            'recaptcha_token' => trim((string) $this->input('recaptcha_token')),
        ]);
    }

    private function normalizePhoneCountryCode(string $phoneCountryCode): string
    {
        $digits = preg_replace('/\D+/', '', $phoneCountryCode) ?? '';

        return $digits === '' ? '' : '+'.$digits;
    }

    private function normalizePhoneNationalNumber(string $phoneNationalNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNationalNumber) ?? '';

        return ltrim($digits, '0');
    }

    private function combinePhoneNumber(string $phoneCountryCode, string $phoneNationalNumber): string
    {
        return $phoneCountryCode.$phoneNationalNumber;
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        return $digits === '' ? '' : '+'.$digits;
    }
}
