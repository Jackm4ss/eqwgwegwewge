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
            'traffic_source' => ['nullable', 'string', 'max:120'],
            'traffic_source_detail' => ['nullable', 'string', 'max:120'],
            'traffic_medium' => ['nullable', 'string', 'max:40'],
            'traffic_campaign' => ['nullable', 'string', 'max:120'],
            'traffic_referrer_host' => ['nullable', 'string', 'max:120'],
            'traffic_landing_path' => ['nullable', 'string', 'max:500'],
            'traffic_captured_at' => ['nullable', 'date'],
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

            $isVerified = app(RecaptchaService::class)->verify($recaptchaToken, $this->ip());

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
            'traffic_source' => $this->normalizeTrafficToken((string) $this->input('traffic_source')),
            'traffic_source_detail' => $this->normalizeOptionalValue((string) $this->input('traffic_source_detail')),
            'traffic_medium' => $this->normalizeTrafficToken((string) $this->input('traffic_medium')),
            'traffic_campaign' => $this->normalizeOptionalValue((string) $this->input('traffic_campaign')),
            'traffic_referrer_host' => $this->normalizeOptionalLowercaseValue((string) $this->input('traffic_referrer_host')),
            'traffic_landing_path' => $this->normalizeOptionalValue((string) $this->input('traffic_landing_path')),
            'traffic_captured_at' => $this->normalizeOptionalValue((string) $this->input('traffic_captured_at')),
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

    private function normalizeOptionalValue(string $value): ?string
    {
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $value));

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeTrafficToken(string $value): ?string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeOptionalLowercaseValue(string $value): ?string
    {
        $normalized = $this->normalizeOptionalValue($value);

        return $normalized !== null ? strtolower($normalized) : null;
    }
}
