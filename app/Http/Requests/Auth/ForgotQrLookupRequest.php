<?php

namespace App\Http\Requests\Auth;

use App\Services\Security\RecaptchaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ForgotQrLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search_type' => ['required', 'string', Rule::in(['email', 'phone', 'passport', 'ic'])],
            'email' => ['nullable', 'email:rfc', 'max:120'],
            'phone_country_code' => ['nullable', 'string', 'regex:/^\+\d{1,4}$/'],
            'phone_national_number' => ['nullable', 'string', 'regex:/^\d{4,20}$/'],
            'phone_number' => ['nullable', 'string', 'max:30', 'regex:/^\+\d{6,20}$/'],
            'country' => ['nullable', 'string', 'max:80'],
            'identity_type' => ['nullable', 'string', Rule::in(['national_id', 'passport'])],
            'identity_number' => ['nullable', 'string', 'max:80'],
            'recaptcha_token' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $searchType = (string) $this->input('search_type');

            match ($searchType) {
                'email' => $this->validateEmailSearch($validator),
                'phone' => $this->validatePhoneSearch($validator),
                'passport' => $this->validatePassportSearch($validator),
                'ic' => $this->validateIcSearch($validator),
                default => null,
            };

            if (! config('services.recaptcha.enabled')) {
                return;
            }

            $recaptchaToken = trim((string) $this->input('recaptcha_token'));

            if ($recaptchaToken === '') {
                $validator->errors()->add(
                    'recaptcha_token',
                    'Please complete the reCAPTCHA verification.'
                );

                return;
            }

            $isVerified = app(RecaptchaService::class)->verify($recaptchaToken, $this->ip());

            if (! $isVerified) {
                $validator->errors()->add(
                    'recaptcha_token',
                    'reCAPTCHA verification failed. Please try again.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $searchType = strtolower(trim((string) $this->input('search_type')));
        $phoneCountryCode = $this->normalizePhoneCountryCode((string) $this->input('phone_country_code'));
        $phoneNationalNumber = $this->normalizePhoneNationalNumber((string) $this->input('phone_national_number'));
        $phoneNumber = '';

        if ($phoneCountryCode !== '' && $phoneNationalNumber !== '') {
            $phoneNumber = $phoneCountryCode.$phoneNationalNumber;
        }

        $identityType = strtolower(trim((string) $this->input('identity_type')));
        $country = strtoupper(trim((string) $this->input('country')));

        if ($searchType === 'passport') {
            $identityType = 'passport';
        }

        if ($searchType === 'ic') {
            $identityType = 'national_id';
            $country = 'MY';
        }

        $this->merge([
            'search_type' => $searchType,
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone_country_code' => $phoneCountryCode !== '' ? $phoneCountryCode : null,
            'phone_national_number' => $phoneNationalNumber !== '' ? $phoneNationalNumber : null,
            'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
            'country' => $country !== '' ? $country : null,
            'identity_type' => $identityType !== '' ? $identityType : null,
            'identity_number' => strtoupper(trim((string) $this->input('identity_number'))),
            'recaptcha_token' => trim((string) $this->input('recaptcha_token')),
        ]);
    }

    private function validateEmailSearch(Validator $validator): void
    {
        if ((string) $this->input('email') === '') {
            $validator->errors()->add('email', 'Email is required.');
        }
    }

    private function validatePhoneSearch(Validator $validator): void
    {
        if ((string) $this->input('phone_country_code') === '') {
            $validator->errors()->add('phone_country_code', 'Country code is required.');
        }

        if ((string) $this->input('phone_national_number') === '') {
            $validator->errors()->add('phone_national_number', 'Phone number is required.');
        }
    }

    private function validatePassportSearch(Validator $validator): void
    {
        if ((string) $this->input('country') === '') {
            $validator->errors()->add('country', 'Country is required.');
        }

        if ((string) $this->input('identity_number') === '') {
            $validator->errors()->add('identity_number', 'Passport Number is required.');
        }
    }

    private function validateIcSearch(Validator $validator): void
    {
        if ((string) $this->input('identity_number') === '') {
            $validator->errors()->add('identity_number', 'Malaysia IC (MyKad) Number is required.');
        }
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
}
