<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

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
            'identity_number' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:120'],
            'phone_number' => ['required', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:80'],
            'agreeTerms' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'full_name' => trim((string) preg_replace('/\s+/u', ' ', (string) $this->input('full_name'))),
            'identity_number' => strtoupper(trim((string) $this->input('identity_number'))),
            'phone_number' => trim((string) $this->input('phone_number')),
        ]);
    }
}
