<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminUpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone_country_code' => ['nullable', 'string', 'max:10'],
            'phone_national_number' => ['nullable', 'string', 'max:25'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'country' => ['required', 'string', 'size:2'],
            'identity_type' => ['required', 'in:passport,national_id'],
            'identity_number' => ['required', 'string', 'max:50'],
            'account_status' => ['required', 'in:pending_verification,active,blocked'],
            'verification_status' => ['required', 'in:unverified,verified'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $country = strtoupper((string) $this->input('country'));
            $identityType = (string) $this->input('identity_type');

            if ($country !== 'MY' && $identityType !== 'passport') {
                $validator->errors()->add(
                    'identity_type',
                    'For participants outside Malaysia, the primary identity document must be a Passport.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $country = strtoupper(trim((string) $this->input('country')));
        $identityType = strtolower(trim((string) $this->input('identity_type')));
        $simplePhoneMode = filter_var($this->input('_simple_phone_mode'), FILTER_VALIDATE_BOOL);

        if ($country !== 'MY') {
            $identityType = 'passport';
        } elseif ($identityType === '') {
            $identityType = 'national_id';
        }

        $this->merge([
            'full_name' => trim((string) preg_replace('/\s+/u', ' ', (string) $this->input('full_name'))),
            'email' => strtolower(trim((string) $this->input('email'))),
            'country' => $country,
            'identity_type' => $identityType,
            'identity_number' => strtoupper(trim((string) $this->input('identity_number'))),
            'phone_country_code' => $simplePhoneMode ? '' : trim((string) $this->input('phone_country_code')),
            'phone_national_number' => $simplePhoneMode ? '' : trim((string) $this->input('phone_national_number')),
            'phone_number' => trim((string) $this->input('phone_number')),
        ]);
    }
}
