<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminQrManagementLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search_type' => ['nullable', 'string', Rule::in(['email', 'entry_code', 'phone', 'passport', 'ic'])],
            'email' => ['nullable', 'email:rfc', 'max:120'],
            'entry_code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/'],
            'phone_country_code' => ['nullable', 'string', 'regex:/^\+\d{1,4}$/'],
            'phone_national_number' => ['nullable', 'string', 'regex:/^\d{4,20}$/'],
            'phone_number' => ['nullable', 'string', 'max:30', 'regex:/^\+\d{6,20}$/'],
            'country' => ['nullable', 'string', 'size:2'],
            'identity_type' => ['nullable', 'string', Rule::in(['national_id', 'passport'])],
            'identity_number' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasLookupAttempt()) {
                return;
            }

            $searchType = (string) $this->input('search_type');

            match ($searchType) {
                'email' => $this->validateEmailSearch($validator),
                'entry_code' => $this->validateEntryCodeSearch($validator),
                'phone' => $this->validatePhoneSearch($validator),
                'passport' => $this->validatePassportSearch($validator),
                'ic' => $this->validateIcSearch($validator),
                default => $validator->errors()->add('search_type', 'Please choose a search method.'),
            };
        });
    }

    public function hasLookupAttempt(): bool
    {
        return filled($this->input('search_type'))
            || filled($this->input('email'))
            || filled($this->input('entry_code'))
            || filled($this->input('phone_national_number'))
            || filled($this->input('identity_number'));
    }

    protected function prepareForValidation(): void
    {
        $searchType = strtolower(trim((string) $this->input('search_type', '')));
        $email = strtolower(trim((string) $this->input('email', '')));
        $entryCode = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $this->input('entry_code', '')) ?? '');
        $phoneCountryCode = $this->normalizePhoneCountryCode((string) $this->input('phone_country_code', ''));
        $phoneNationalNumber = $this->normalizePhoneNationalNumber((string) $this->input('phone_national_number', ''));
        $country = strtoupper(trim((string) $this->input('country', '')));
        $identityType = strtolower(trim((string) $this->input('identity_type', '')));
        $identityNumber = strtoupper(trim((string) $this->input('identity_number', '')));

        if ($searchType === 'passport') {
            $identityType = 'passport';
        }

        if ($searchType === 'ic') {
            $identityType = 'national_id';
            $country = 'MY';
        }

        $phoneNumber = '';

        if ($phoneCountryCode !== '' && $phoneNationalNumber !== '') {
            $phoneNumber = $phoneCountryCode.$phoneNationalNumber;
        }

        $this->merge([
            'search_type' => $searchType !== '' ? $searchType : null,
            'email' => $email !== '' ? $email : null,
            'entry_code' => $entryCode !== '' ? $entryCode : null,
            'phone_country_code' => $phoneCountryCode !== '' ? $phoneCountryCode : null,
            'phone_national_number' => $phoneNationalNumber !== '' ? $phoneNationalNumber : null,
            'phone_number' => $phoneNumber !== '' ? $phoneNumber : null,
            'country' => $country !== '' ? $country : null,
            'identity_type' => $identityType !== '' ? $identityType : null,
            'identity_number' => $identityNumber !== '' ? $identityNumber : null,
        ]);
    }

    private function validateEmailSearch(Validator $validator): void
    {
        if ((string) $this->input('email') === '') {
            $validator->errors()->add('email', 'Email is required.');
        }
    }

    private function validateEntryCodeSearch(Validator $validator): void
    {
        if ((string) $this->input('entry_code') === '') {
            $validator->errors()->add('entry_code', 'Entry code is required.');
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
            $validator->errors()->add('identity_number', 'Passport number is required.');
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
