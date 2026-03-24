<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'agreeTerms' => ['accepted'],
        ];
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
